<?php

namespace App\Bundles\pqr\Services;

use App\services\exception\SaiaException;
use App\services\GlobalContainer;
use App\services\models\ModelService\ModelService;
use Saia\core\db\customDrivers\OtherQueriesForPlatform;
use Saia\models\formatos\Formato;
use App\Bundles\pqr\Services\models\PqrForm;
use Saia\models\busqueda\BusquedaComponente;
use App\Bundles\pqr\Services\models\PqrFormField;
use App\Bundles\pqr\Services\controllers\AddEditFormat\AddEditFtPqr;
use Saia\models\grupo\Grupo;
use Saia\models\Modulo;
use Saia\models\tarea\Tarea;
use Saia\models\tarea\TareaEstado;
use Saia\models\tarea\TareaFuncionario;

class PqrFormService extends ModelService
{
    /**
     * Ruta del Ws de PQR
     *
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com> @date 2021-02-25
     */
    public static function getUrlWsPQR(): string
    {
        return $_SERVER['APP_DOMAIN'] . 'ws/pqr/index.html';
    }

    /**
     * Obtiene la instancia de PqrForm actualizada
     *
     * @return PqrForm
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function getModel(): PqrForm
    {
        return $this->Model;
    }

    /**
     * Actualiza los datos de configuracion del formulario
     *
     * @param array $data
     * @return boolean
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function updateSetting(array $data): bool
    {
        if (!$this->update($data['pqrForm'])) {
            $this->getErrorManager()->setMessage("No fue posible actualizar");
            return false;
        }

        GlobalContainer::getConnection()
            ->createQueryBuilder()
            ->update('pqr_form_fields')
            ->set('anonymous', 0)
            ->set('required_anonymous', 0)
            ->where("name<>'sys_tipo'")->execute();

        if ($this->getModel()->show_anonymous) {
            if ($formFields = $data['formFields']) {
                foreach ($formFields['dataShowAnonymous'] as $id) {

                    $attributes = [
                        'anonymous' => 1
                    ];
                    if ($dataRequired = $formFields['dataRequiredAnonymous']) {
                        if (in_array($id, $dataRequired)) {
                            $attributes['required_anonymous'] = 1;
                        }
                    }

                    $PqrFormFieldService = (new PqrFormField($id))->getService();
                    if (!$PqrFormFieldService->update($attributes)) {
                        $this->getErrorManager()->setMessage("No fue posible actualizar");
                        return false;
                    }
                }
            }
        }

        return true;
    }

    /**
     * Actualiza la configuracion para la respuesta
     *
     * @param array $data
     * @return boolean
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function updateResponseSetting(array $data): bool
    {

        $info = [];
        foreach ($data['tercero'] as $name => $value) {
            $info[] = [
                'name'  => $name,
                'value' => $value
            ];
        }

        return $this->update([
            'response_configuration' => json_encode(['tercero' => $info])
        ]);
    }

    /**
     * Obtiene todos los datos del modulo de configuracion
     *
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function getSetting(): array
    {
        $options = $this->getDataresponseTime();
        return [
            'urlWs'               => static::getUrlWsPQR(),
            'publish'             => $this->getModel()->fk_formato ? 1 : 0,
            'pqrForm'             => $this->getDataPqrForm(),
            'pqrFormFields'       => $this->getDataPqrFormFields(),
            'pqrNotifications'    => $this->getDataPqrNotifications(),
            'optionsNotyMessages' => PqrNotyMessageService::getDataPqrNotyMessages(),
            'responseTimeOptions' => $options,
            'balancerOptions'     => $options,
            'groupOptions'        => $this->getGroupsForBalancer(),
            'descriptionField'    => $this->getdescriptionField()
        ];
    }

    private function getGroupsForBalancer(): array
    {
        $Groups = Grupo::findAllByAttributes([
            'estado' => 1
        ]);

        $dataGroups = [];
        foreach ($Groups as $Grupo) {
            $dataGroups[] = [
                'id'   => $Grupo->getPK(),
                'name' => $Grupo->nombre
            ];
        }
        return $dataGroups;
    }

    /**
     * publica o crea el formulario en el webservice
     *
     * @return boolean
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function publish(): bool
    {
        (new AddEditFtPqr($this->getModel()))->updateChange();

        $this->getModel()->getFormatoFk()->getService()->generate();

        if (!$this->getModel()->fk_field_time) {
            $this->editFieldTime(PqrFormField::getSysTipoField()->fk_campos_formato);
        }

        if (!$FormatoR = Formato::findByAttributes([
            'nombre' => 'pqr_respuesta'
        ])) {
            $this->getErrorManager()->setMessage("El formato de respuesta PQR no fue encontrado");
            return false;
        }

        $formatNameR = "COMUNICACIÓN EXTERNA ({$this->getModel()->label})";
        if ($FormatoR->etiqueta != $formatNameR) {
            $FormatoR->etiqueta = $formatNameR;
            $FormatoR->save();
        }
        $FormatoR->getService()->generate();

        if (!$FormatoC = Formato::findByAttributes([
            'nombre' => 'pqr_calificacion'
        ])) {
            $this->getErrorManager()->setMessage("El formato de calificacion PQR no fue encontrado");
            return false;
        }

        $formatNameC = "CALIFICACIÓN ({$this->getModel()->label})";
        if ($FormatoC->etiqueta != $formatNameC || !$FormatoC->webservice) {
            $FormatoC->etiqueta = $formatNameC;
            $FormatoC->webservice = 1;

            if (!$FormatoC->clase_ws) {
                $FormatoC->clase_ws = 'App\Bundles\pqr\Services\generadoresWs\GenerateWsPqrCalificacion';
            }

            $FormatoC->save();
        }
        $FormatoC->getService()->generate();

        $this->generaReport();
        $this->viewRespuestaPqr();
        $this->viewCalificacionPqr();
        $this->viewPqrTarea();

        PqrService::activeGraphics();
        $this->activeInfoForDependency();

        return true;
    }

    /**
     * Activa el reporte de Dependencia  o PQR por dependencia
     * cuando se activa el compoenten de sys_dependencia
     *
     * @throws SaiaException
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2022-04-08
     */
    private function activeInfoForDependency()
    {
        $PqrFormField = PqrFormField::findByAttributes([
            'name' => 'sys_dependencia'
        ]);

        if (!$PqrFormField) {
            return;
        }

        if (Modulo::findByAttributes([
            'nombre' => PqrForm::NOMBRE_REPORTE_POR_DEPENDENCIA,
        ])) {
            return;
        }

        $ModuloPadre = Modulo::findByAttributes([
            'nombre' => 'reporte_pqr'
        ]);

        if (!$ModuloPadre) {
            throw new SaiaException("No se encontro el modulo del Reporte");
        }

        $BusquedaComponente = BusquedaComponente::findByAttributes([
            'nombre' => PqrForm::NOMBRE_REPORTE_POR_DEPENDENCIA,
        ]);

        $enlace = 'views/dashboard/kaiten_dashboard.php?panels=[{"kConnector":"iframe","url": "views/buzones/grilla.php?idbusqueda_componente=' . $BusquedaComponente->getPK() . '"}]';
        $data = [
            'pertenece_nucleo' => 0,
            'nombre'           => PqrForm::NOMBRE_REPORTE_POR_DEPENDENCIA,
            'tipo'             => Modulo::TIPO_HIJO,
            'imagen'           => 'fa fa-bar-chart-o',
            'etiqueta'         => 'Por Dependencia',
            'enlace'           => $enlace,
            'cod_padre'        => $ModuloPadre->getPK(),
            'orden'            => 4,
            'asignable'        => 1,
            'tiene_hijos'      => 0
        ];

        $ModuloService = (new Modulo())->getService();
        if (!$ModuloService->save($data)) {
            throw new SaiaException("No fue posible registrar el reporte de PQR por Dependencia");
        }
    }

    /**
     * Obtiene los campos del formulario
     *
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function getDataPqrFormFields(): array
    {
        $data = [];
        if ($records = $this->getModel()->getPqrFormFields()) {
            foreach ($records as $PqrFormField) {
                $data[] = $PqrFormField->getDataAttributes();
            }
        }
        return $data;
    }

    /**
     * Obtiene los datos de construccion del formulario
     *
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function getDataPqrForm(): array
    {
        return $this->getModel()->getDataAttributes();
    }

    /**
     * Obtiene las notificaciones
     *
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function getDataPqrNotifications(): array
    {
        $data = [];
        if ($records = $this->getModel()->getPqrNotifications()) {
            foreach ($records as $PqrNotification) {
                $data[] = $PqrNotification->getDataAttributes();
            }
        }
        return $data;
    }

    /**
     * Actualiza el reporte
     *
     * @return void
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function generaReport(): void
    {
        $this->viewPqr();
        $fields = $this->getFieldsReport();
        $this->generateFuncionReport($fields);
        $this->updateReport($fields);
    }

    /**
     * Obtiene los campos en el reporte
     *
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    private function getFieldsReport(): array
    {
        $data = [];
        $fields = $this->getModel()->getPqrFormFields();
        foreach ($fields as $PqrFormField) {
            if ($PqrFormField->show_report) {
                $data[] = $PqrFormField;
            }
        }
        return $data;
    }

    /**
     * Obtiene los campos para crear la vista
     *
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2021-06-30
     */
    private function getFieldsView(): array
    {
        $data = [];
        $fields = $this->getModel()->getPqrFormFields();
        foreach ($fields as $PqrFormField) {
            $data[] = "ft.$PqrFormField->name";
        }
        return $data;
    }

    /**
     * Genera el SQL de la vista PQR
     *
     * @return void
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    private function viewPqr(): void
    {
        $fields = implode(',', array_merge(
            $this->defaultFieldsReport(),
            $this->getFieldsView()
        ));

        $sql = "SELECT $fields
        FROM ft_pqr ft,documento d
        WHERE ft.documento_iddocumento=d.iddocumento
        AND d.estado = 'APROBADO'";

        $this->createView('vpqr', $sql);
    }

    /**
     * Campos por defecto
     *
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2021
     */
    private function defaultFieldsReport(): array
    {
        return [
            'd.iddocumento',
            'd.numero',
            'd.fecha',
            'd.canal_recepcion',
            'ft.sys_estado',
            'ft.sys_fecha_vencimiento',
            'ft.sys_fecha_terminado',
            'ft.sys_frecuencia',
            'ft.sys_impacto',
            'ft.sys_severidad',
            'ft.sys_oportuno',
            'ft.idft_pqr as idft'
        ];
    }

    /**
     * Informacion del campo tipo descripción por defecto
     *
     * @return array
     * @author Julian Otalvaro <julian.otalvaro@cerok.com>
     * @since  2023-09-27
     */
    private function getDescriptionField(): array
    {
        $pqrFormId = $this->getDataPqrForm()['description_field'];
        $data = [];

        if ($pqrFormId) {
            $PqrFormField = new PqrFormField($pqrFormId);
            $data = [
                "id"   => $pqrFormId,
                "name" => $PqrFormField->label
            ];
        }

        return $data;
    }

    /**
     * Crea la vista en la DB
     *
     * @param string $name
     * @param string $select
     * @return void
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    private function createView(string $name, string $select): void
    {
        $OtherQueriesForPlatform = new OtherQueriesForPlatform();
        $OtherQueriesForPlatform->createView($name, $select);
    }

    /**
     * Genera el archivo de funciones para el reporte
     *
     * @param PqrFormField[] $fields
     * @return void
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    private function generateFuncionReport(array $fields): void
    {
        $fieldCode = [];
        foreach ($fields as $PqrFormField) {
            $code = '';
            switch ($PqrFormField->getPqrHtmlField()->type_saia) {
                // case 'Textarea':
                //     $code = "function get_{$PqrFormField->name}(int \$idft,\$value){
                //         return substr(\$value, 0, 30).' ...';
                //     }";
                //     break;
                case 'Select':
                case 'Radio':
                    $code = "function get_$PqrFormField->name(int \$idft,\$value){
                        global \$FtPqr;
                        \$response = '';
                        if (\$valor = Saia\\models\\formatos\\CampoSeleccionados::findColumn('valor', [
                            'fk_campo_opciones' => \$value,
                            'fk_documento' => \$FtPqr->documento_iddocumento
                        ])) {
                            \$response = \$valor[0];
                        }
                        return \$response;
                    }";
                    break;
                case 'Checkbox':
                    $code = "function get_$PqrFormField->name(int \$idft,\$value){
                        global \$FtPqr;
                        \$response = '';
                        if (\$valor = Saia\\models\\formatos\\CampoSeleccionados::findColumn('valor', [
                            'fk_campos_formato' => $PqrFormField->fk_campos_formato,
                            'fk_documento' => \$FtPqr->documento_iddocumento
                        ])) {
                            \$response = implode(',',\$valor);
                        }
                        return \$response;
                    }";
                    break;
                case 'AutocompleteM':
                case 'AutocompleteD':
                    $code = "function get_$PqrFormField->name(int \$idft,\$value){
                        global \$FtPqr;
                        return \$FtPqr->getService()->getValueForReport('$PqrFormField->name');
                    }";
                    break;
                case 'Date':
                    $code = "function get_$PqrFormField->name(int \$idft,\$value){
                          return \$value ? dateRadication(\$value) : '';
                    }";
                    break;
            }
            if ($code) {
                $fieldCode[] = $code;
            }
        }
        $file = $_SERVER["ROOT_PATH"] . 'src/Bundles/pqr/formatos/pqr/functionsReport.php';
        if (file_exists($file)) {
            unlink($file);
        }
        $codeFunction = "<?php \n\n" . implode("\n", $fieldCode);

        if (!file_put_contents($file, $codeFunction)) {
            throw new SaiaException("No fue posible crear las funciones del formulario");
        }
    }

    /**
     * actualiza el reporte (busqueda componente)
     *
     * @param PqrFormField[] $fields
     * @return void
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    private function updateReport(array $fields): void
    {
        $selectedFields = $nameOfSeletedFields = [];
        foreach ($fields as $PqrFormField) {
            $nameOfSeletedFields[] = $PqrFormField->name;
            $type = $PqrFormField->getPqrHtmlField()->type_saia;
            switch ($type) {
                case 'Text':
                case 'Textarea':
                    $selectedFields[] = [
                        'title' => strtoupper($PqrFormField->label),
                        'field' => "{*$PqrFormField->name*}",
                        'align' => 'center',
                    ];
                    break;
                default:
                    $selectedFields[] = [
                        'title' => strtoupper($PqrFormField->label),
                        'field' => "{*get_$PqrFormField->name@idft,$PqrFormField->name*}",
                        'align' => 'center',
                    ];
                    break;
            }
        }

        //REPORTE PENDIENTE
        if ($Pendiente = BusquedaComponente::findByAttributes([
            'nombre' => PqrForm::NOMBRE_REPORTE_PENDIENTE
        ])) {
            $Pendiente->setAttributes(
                $this->getDefaultDataComponente(
                    $selectedFields,
                    $nameOfSeletedFields,
                    PqrForm::NOMBRE_REPORTE_PENDIENTE)
            );
            $Pendiente->save();
        }

        //REPORTE PROCESO
        if ($Proceso = BusquedaComponente::findByAttributes([
            'nombre' => PqrForm::NOMBRE_REPORTE_PROCESO
        ])) {
            $Proceso->setAttributes(
                $this->getDefaultDataComponente(
                    $selectedFields,
                    $nameOfSeletedFields,
                    PqrForm::NOMBRE_REPORTE_PROCESO)
            );
            $Proceso->save();
        }

        //REPORTE TERMINADO
        if ($Terminado = BusquedaComponente::findByAttributes([
            'nombre' => PqrForm::NOMBRE_REPORTE_TERMINADO
        ])) {
            $Terminado->setAttributes(
                $this->getDefaultDataComponente(
                    $selectedFields,
                    $nameOfSeletedFields,
                    PqrForm::NOMBRE_REPORTE_TERMINADO)
            );
            $Terminado->save();
        }

        //REPORTE TODOS
        if ($Todos = BusquedaComponente::findByAttributes([
            'nombre' => PqrForm::NOMBRE_REPORTE_TODOS
        ])) {
            $Todos->setAttributes(
                $this->getDefaultDataComponente(
                    $selectedFields,
                    $nameOfSeletedFields,
                    PqrForm::NOMBRE_REPORTE_TODOS)
            );
            $Todos->save();
        }
    }


    /**
     * Obtiene los campos y el info por defecto
     * de los reportes (busqueda componente)
     *
     * @param array  $selectedFields
     * @param array  $nameOfSeletedFields
     * @param string $reportName
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    private function getDefaultDataComponente(
        array $selectedFields,
        array $nameOfSeletedFields,
        string $reportName
    ): array {

        $info = array_merge(
            [
                [
                    'title' => 'RADICADO',
                    'field' => '{*viewFtPqr@idft,numero*}',
                    'align' => 'center'
                ],
                [
                    'title' => 'FECHA',
                    'field' => '{*dateRadication@fecha*}',
                    'align' => 'center'
                ],
            ],
            $selectedFields,
            [
                [
                    'title' => 'TIPO',
                    'field' => '{*getValueSysTipo@iddocumento,sys_tipo*}',
                    'align' => 'center'
                ],
                [
                    'title' => 'OPORTUNIDAD EN LAS RESPUESTAS',
                    'field' => '{*sys_oportuno*}',
                    'align' => 'center'
                ],
                [
                    'title' => 'CANAL DE RECEPCIÓN',
                    'field' => '{*canal_recepcion*}',
                    'align' => 'center'
                ],
            ],
            $this->getFieldsByStateForReport($reportName),
            [
                [
                    'title' => 'OPCIONES',
                    'field' => '{*options@iddocumento,sys_estado,idft*}',
                    'align' => 'center'
                ]
            ]
        );

        $fieldNames = array_merge([
            'v.numero',
            'v.fecha',
            'v.sys_tipo',
            'v.sys_estado',
            'v.idft',
            'v.sys_oportuno',
            'v.canal_recepcion'
        ], $nameOfSeletedFields);

        return [
            'info'               => json_encode($info),
            'campos_adicionales' => implode(',', $fieldNames)
        ];
    }

    /**
     * Genera el SQL de la vista respuesta a la PQR
     *
     * @return void
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    private function viewRespuestaPqr(): void
    {
        $sql = "SELECT d.iddocumento,d.numero,d.fecha,ft.idft_pqr_respuesta as idft,ft.ft_pqr
        FROM ft_pqr_respuesta ft,documento d
        WHERE ft.documento_iddocumento=d.iddocumento AND d.estado NOT IN ('ELIMINADO')";

        $this->createView('vpqr_respuesta', $sql);
    }

    /**
     * Genera el SQL de la vista calificacion a la PQR
     *
     * @return void
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    private function viewCalificacionPqr(): void
    {
        $sql = <<<SQL
SELECT d.iddocumento AS iddocumento, d.numero AS numero, d.fecha AS fecha, ft.idft_pqr_calificacion AS idft, ft.ft_pqr_respuesta AS ft_pqr_respuesta, ft.experiencia_gestion AS experiencia_gestion, ft.experiencia_servicio AS experiencia_servicio, ftr.ft_pqr as idft_pqr
FROM ft_pqr_calificacion ft, documento d, ft_pqr_respuesta ftr
WHERE ft.documento_iddocumento = d.iddocumento AND ftr.idft_pqr_respuesta = ft.ft_pqr_respuesta AND d.estado <> 'ELIMINADO'
SQL;
        $this->createView('vpqr_calificacion', $sql);
    }

    /**
     *
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2024-02-21
     */
    private function viewPqrTarea()
    {
        $tRelacion = Tarea::RELACION_DOCUMENTO;
        $teEstados = implode(',', [
            TareaEstado::PENDIENTE,
            TareaEstado::PROCESO,
            TareaEstado::DEVUELTA,
        ]);
        $tfTipo=TareaFuncionario::TYPE_MANAGER;
        $tfExterno=TareaFuncionario::INTERNAL_USER;

        $sql = <<<SQL
SELECT tf.usuario as idfuncionario,count(tf.usuario) as cant_task
FROM vpqr p
JOIN tarea t ON p.iddocumento=t.relacion_id
JOIN tarea_funcionario tf ON tf.fk_tarea=t.idtarea
JOIN tarea_estado te ON te.fk_tarea=t.idtarea
WHERE t.relacion=$tRelacion
AND tf.tipo=$tfTipo AND tf.externo=$tfExterno
AND te.valor IN ($teEstados) AND te.estado=1
GROUP BY tf.usuario
SQL;

        $this->createView('vpqr_tareas', $sql);
    }

    /**
     * Obtiene los campos que se utilizaran para la combinacion
     * de dias de respuesta
     *
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2021-06-05
     */
    private function getDataresponseTime(): array
    {
        $data = [];
        if ($records = $this->getModel()->getPqrFormFields()) {
            foreach ($records as $PqrFormField) {
                $PqrHtmlField = $PqrFormField->getPqrHtmlField();

                if (
                    $PqrHtmlField->isValidFieldForResponseDaysOrBalance() &&
                    $PqrFormField->isActive() &&
                    $PqrFormField->fk_campos_formato
                ) {
                    $fieldOptions = [];

                    if ($PqrFormField->name != PqrFormField::FIELD_NAME_SYS_TIPO) {
                        $options = $PqrFormField->getCamposFormato()->getCampoOpciones(['estado' => 1]);
                        foreach ($options as $CampoOpcion) {
                            if ($CampoOpcion->estado) {
                                $fieldOptions[] = [
                                    'id'    => $CampoOpcion->getPK(),
                                    'label' => $CampoOpcion->valor
                                ];
                            }
                        }
                    }

                    $data[] = [
                        'id'      => $PqrFormField->fk_campos_formato,
                        'label'   => $PqrFormField->label,
                        'options' => $fieldOptions
                    ];
                }
            }
        }

        return $data;
    }

    /**
     * Actualiza el campo que define los tiempos de respuesta
     *
     * @param int $idCampoFormato
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2021-06-09
     */
    public function editFieldTime(int $idCampoFormato): void
    {
        $this->save([
            'fk_field_time' => $idCampoFormato
        ]);
    }

    /**
     * Obtiene las columnas que tendran las columnas de los reportes
     *
     * @param string $reportName
     * @return array[]
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2023-10-05
     */
    private function getFieldsByStateForReport(string $reportName): array
    {
        $defaultFiels = [
            [
                'title' => 'TAREAS',
                'field' => '{*totalTask@iddocumento*}',
                'align' => 'center'
            ],
            [
                'title' => 'RESPONSABLES',
                'field' => '{*getResponsible@iddocumento*}',
                'align' => 'center'
            ],
            [
                'title' => 'RESPUESTAS',
                'field' => '{*totalAnswers@idft*}',
                'align' => 'center'
            ],
            [
                'title' => 'CALIFICACIÓN GESTIÓN',
                'field' => '{*qualificationGest@idft*}',
                'align' => 'center'
            ],
            [
                'title' => 'CALIFICACIÓN SERVICIO',
                'field' => '{*qualificationServ@idft*}',
                'align' => 'center'
            ]
        ];

        $otherDefaultFields = [
            [
                'title' => 'DIAS DE ESPERA',
                'field' => '{*getDaysWait@idft*}',
                'align' => 'center'
            ],
            [
                'title' => 'FECHA VENCIMIENTO',
                'field' => '{*getExpiration@idft*}',
                'align' => 'center'
            ]
        ];

        switch ($reportName) {
            case PqrForm::NOMBRE_REPORTE_TODOS:
                $fieldForReport = array_merge([
                    [
                        'title' => 'ESTADO',
                        'field' => '{*sys_estado*}',
                        'align' => 'center'
                    ],
                ], $otherDefaultFields, $defaultFiels);
                break;
            case PqrForm::NOMBRE_REPORTE_PROCESO:
                $fieldForReport = array_merge($otherDefaultFields, $defaultFiels);
                break;
            case PqrForm::NOMBRE_REPORTE_TERMINADO:
                $fieldForReport = array_merge([
                    [
                        'title' => 'DÍAS RETRASO',
                        'field' => '{*getDaysLate@idft*}',
                        'align' => 'center'
                    ],
                    [
                        'title' => 'FECHA FINALIZACIÓN',
                        'field' => '{*getEndDate@idft*}',
                        'align' => 'center'
                    ],
                ], $defaultFiels);
                break;
            case PqrForm::NOMBRE_REPORTE_PENDIENTE:
            default:
                $fieldForReport = $otherDefaultFields;
                break;
        }

        $FtClassName = $this->getModel()->getFormatoFk()->getFtClass();
        if (method_exists($FtClassName, 'getCustomColumnsForReport')) {
            $fieldForReport = array_merge($fieldForReport, $FtClassName::getCustomColumnsForReport($reportName));
        }

        return $fieldForReport;
    }

    /**
     * Actualiza el campo descripcion adicional que se adicionara al formulario de PQR
     *
     * @param int $fieldId
     * @return bool
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2023-10-11
     */
    public function updateFieldDescription(int $fieldId): bool
    {
        $PqrForms = $this->getModel();

        if ($PqrForms->description_field && $PqrForms->description_field == $fieldId) {
            return true;
        }

        $CamposFormato = (new PqrFormField($fieldId))->getCamposFormato();
        if ($CamposFormato->isDescriptionField()) {
            return true;
        }

        $actionList = explode(',', $CamposFormato->acciones);
        $actionList[] = 'p';

        $CamposFormatoService = $CamposFormato->getService();
        $success = $CamposFormato->getService()->save([
            'acciones' => implode(',', array_filter($actionList))
        ]);

        if (!$success) {
            $this->setErrorManager($CamposFormatoService->getErrorManager());
            return false;
        }

        $PqrFormFieldDes = $PqrForms->getPqrFormFieldDescription();
        if ($PqrFormFieldDes) {
            $CamposFormatoOld = $PqrFormFieldDes->getCamposFormato();

            $actionListOld = array_diff(explode(',', $CamposFormatoOld->acciones), ['p']);

            $CamposFormatoServiceOld = $CamposFormatoOld->getService();
            $success = $CamposFormatoServiceOld->save([
                'acciones' => implode(',', array_filter($actionListOld))
            ]);

            if (!$success) {
                $this->setErrorManager($CamposFormatoService->getErrorManager());
                return false;
            }
        }

        return $PqrForms->getService()->save([
            'description_field' => $fieldId
        ]);
    }


}
