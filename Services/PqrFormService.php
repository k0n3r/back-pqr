<?php

namespace App\Bundles\pqr\Services;

use App\Bundles\pqr\Entity\PqrForm as PqrFormEntity;
use App\Bundles\pqr\Entity\PqrFormField as PqrFormFieldEntity;
use App\Bundles\pqr\Entity\PqrNotification as PqrNotificationEntity;
use App\Bundles\pqr\Entity\PqrNotyMessage as PqrNotyMessageEntity;
use App\Bundles\pqr\Repository\PqrFormFieldRepository;
use App\Bundles\pqr\Repository\PqrFormRepository;
use App\Bundles\pqr\Repository\PqrNotificationRepository;
use App\Bundles\pqr\Service\PqrFormFieldServiceFactory;
use App\Bundles\pqr\Services\controllers\AddEditFormat\AddEditFtPqr;
use App\Entity\EmailConfiguration;
use App\services\Service;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Saia\core\db\customDrivers\OtherQueriesForPlatform;
use Saia\models\busqueda\BusquedaComponente;
use Saia\models\formatos\CamposFormato;
use Saia\models\formatos\Formato;
use Saia\models\Funcionario;
use Saia\models\grupo\Grupo;
use Saia\models\Modulo;
use Saia\models\tarea\Tarea;
use Saia\models\tarea\TareaEstado;
use Saia\models\tarea\TareaFuncionario;

class PqrFormService extends Service
{
    private PqrFormEntity $entity;

    public function __construct(
        private EntityManagerInterface $em,
        private PqrFormRepository $pqrFormRepository,
        private PqrFormFieldRepository $pqrFormFieldRepository,
        private PqrFormFieldServiceFactory $pqrFormFieldServiceFactory,
        ?Funcionario $funcionario = null,
    ) {
        parent::__construct($funcionario);
        $this->entity = $pqrFormRepository->findActiveOrFail();
    }

    public function save(array $attributes): bool
    {
        $this->applyAttributes($attributes);
        $this->em->flush();

        return true;
    }

    protected function update(array $attributes): bool
    {
        return $this->save($attributes);
    }

    private function applyAttributes(array $attributes): void
    {
        foreach ($attributes as $key => $value) {
            match ($key) {
                'label'                  => $this->entity->setLabel((string)$value),
                'name'                   => $this->entity->setName((string)$value),
                'show_anonymous'         => $this->entity->setShowAnonymous((bool)$value),
                'show_label'             => $this->entity->setShowLabel((bool)$value),
                'show_empty'             => $this->entity->setShowEmpty((bool)$value),
                'fk_formato'             => $this->entity->setFkFormato((int)$value),
                'fk_contador'            => $this->entity->setFkContador((int)$value),
                'fk_field_time'          => $this->entity->setFkFieldTime((int)$value),
                'enable_filter_dep'      => $this->entity->setEnableFilterDep((bool)$value),
                'description_field'      => $this->entity->setDescriptionField((int)$value),
                'enable_balancer'        => $this->entity->setEnableBalancer((bool)$value),
                'enable_con_days'        => $this->entity->setEnableConDays((bool)$value),
                'fk_field_balancer'      => $this->entity->setFkFieldBalancer((int)$value),
                'response_configuration' => $this->entity->setResponseConfiguration(
                    is_string($value) ? json_decode($value, true) : (array)$value
                ),
                'canal_recepcion'        => $this->entity->setCanalRecepcion(
                    is_string($value) ? json_decode($value, true) : (array)$value
                ),
                'active'                 => $this->entity->setActive((bool)$value),
                default                  => null,
            };
        }
    }


    /**
     * Ruta del Ws de PQR
     *
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com> @date 2021-02-25
     */
    public static function getUrlWsPQR(): string
    {
        return $_SERVER['APP_DOMAIN'].'ws/pqr/index.html';
    }

    /**
     * Obtiene la instancia de PqrForm actualizada
     *
     * @return PqrForm
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function getModel(): PqrFormEntity
    {
        return $this->entity;
    }

    public function getResponseConfiguration(bool $inArray = false): object|array|null
    {
        $config = $this->entity->getResponseConfiguration();
        if ($config === null) {
            return null;
        }

        return $inArray ? $config : json_decode(json_encode($config));
    }

    /**
     * Actualiza los datos de configuracion del formulario
     *
     * @param array $data
     * @return bool
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function updateSetting(array $data): bool
    {
        if (!$this->update($data['pqrForm'])) {
            $this->getErrorManager()->setMessage("No fue posible actualizar");

            return false;
        }

        $this->em->getConnection()
            ->createQueryBuilder()
            ->update('pqr_form_fields')
            ->set('anonymous', 0)
            ->set('required_anonymous', 0)
            ->where("name<>'sys_tipo'")->executeStatement();

        if ($this->entity->isShowAnonymous()) {
            if ($formFields = $data['formFields']) {
                foreach ($formFields['dataShowAnonymous'] as $id) {
                    $attributes = [
                        'anonymous' => 1,
                    ];
                    if ($dataRequired = $formFields['dataRequiredAnonymous']) {
                        if (in_array($id, $dataRequired)) {
                            $attributes['required_anonymous'] = 1;
                        }
                    }

                    $PqrFormFieldService = $this->pqrFormFieldServiceFactory->create((int)$id);
                    if (!$PqrFormFieldService->save($attributes)) {
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
     * @return bool
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function updateResponseSetting(array $data): bool
    {
        $info = [];
        foreach ($data['tercero'] as $name => $value) {
            $info[] = [
                'name'  => $name,
                'value' => $value,
            ];
        }

        return $this->update([
            'response_configuration' => json_encode(['tercero' => $info]),
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
            'publish'             => $this->entity->getFkFormato() ? 1 : 0,
            'pqrForm'             => $this->getDataPqrForm(),
            'pqrFormFields'       => $this->getDataPqrFormFields(),
            'pqrNotifications'    => $this->getDataPqrNotifications(),
            'optionsNotyMessages' => $this->getDataPqrNotyMessages(),
            'responseTimeOptions' => $options,
            'balancerOptions'     => $options,
            'groupOptions'        => $this->getGroupsForBalancer(),
            'descriptionField'    => $this->getdescriptionField(),
            'receivingChannel'    => $this->entity->getCanalRecepcion(),
            'emailsConfig'        => $this->getEmailsConfig(),
        ];
    }

    private function getEmailsConfig(): array
    {
        $repository = $this->em->getRepository(EmailConfiguration::class);
        $emailsConfiguration = $repository->findByPqrModule();

        return array_map(fn ($config) => $config->toArray(), $emailsConfiguration);
    }

    private function getGroupsForBalancer(): array
    {
        $Groups = Grupo::findAllByAttributes([
            'estado' => 1,
        ]);

        $dataGroups = [];
        foreach ($Groups as $Grupo) {
            $dataGroups[] = [
                'id'   => $Grupo->getPK(),
                'name' => $Grupo->nombre,
            ];
        }

        return $dataGroups;
    }

    /**
     * publica o crea el formulario en el webservice
     *
     * @return bool
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function publish(): bool
    {
        (new AddEditFtPqr($this->entity))->updateChange();

        (new Formato($this->entity->getFkFormato()))->getService()->generate();

        if (!$this->entity->getFkFieldTime()) {
            $sysTipoField = $this->pqrFormFieldRepository->findSysTipo();
            $this->editFieldTime($sysTipoField ? $sysTipoField->getFkCamposFormato() : 0);
        }

        if (!$FormatoR = Formato::findByAttributes([
            'nombre' => 'pqr_respuesta',
        ])) {
            $this->getErrorManager()->setMessage("El formato de respuesta PQR no fue encontrado");

            return false;
        }

        $formatNameR = "COMUNICACIÓN EXTERNA ({$this->entity->getLabel()})";
        if ($FormatoR->etiqueta != $formatNameR) {
            $FormatoR->etiqueta = $formatNameR;
            $FormatoR->save();
        }
        $FormatoR->getService()->generate();

        if (!$FormatoC = Formato::findByAttributes([
            'nombre' => 'pqr_calificacion',
        ])) {
            $this->getErrorManager()->setMessage("El formato de calificacion PQR no fue encontrado");

            return false;
        }

        $formatNameC = "CALIFICACIÓN ({$this->entity->getLabel()})";
        if ($FormatoC->etiqueta != $formatNameC || !$FormatoC->isEnabledWs()) {
            $FormatoC->etiqueta = $formatNameC;
            $FormatoC->info_ws = json_encode(array_merge($FormatoC->getInfoWs(), [
                'habilita_webservice' => 1,
            ]));

            $FormatoC->save();
        }
        $FormatoC->getService()->generate();

        $this->generaReport();
        $this->viewRespuestaPqr();
        $this->viewCalificacionPqr();
        $this->viewPqrTarea();

        (new PqrService())->activeGraphics();
        $this->activeInfoForDependency();

        return true;
    }

    /**
     * Activa el reporte de Dependencia  o PQR por dependencia
     * cuando se activa el compoenten de sys_dependencia
     *
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2022-04-08
     */
    private function activeInfoForDependency(): void
    {
        $PqrFormField = $this->pqrFormFieldRepository->findByName(PqrFormFieldEntity::FIELD_NAME_SYS_DEPENDENCIA);

        if (!$PqrFormField) {
            return;
        }

        if (Modulo::findByAttributes([
            'nombre' => PqrFormEntity::NOMBRE_REPORTE_POR_DEPENDENCIA,
        ])) {
            return;
        }

        $ModuloPadre = Modulo::findByAttributes([
            'nombre' => 'reporte_pqr',
        ]);

        if (!$ModuloPadre) {
            $trans = $this->serviceLocator->getTranslator()->trans("no_se_encontro_modulo_reporte");
            throw new RuntimeException($trans);
        }

        $BusquedaComponente = BusquedaComponente::findByAttributes([
            'nombre' => PqrFormEntity::NOMBRE_REPORTE_POR_DEPENDENCIA,
        ]);

        $enlace = 'views/dashboard/kaiten_dashboard.php?panels=[{"kConnector":"iframe","url": "views/buzones/grilla.php?idbusqueda_componente='.$BusquedaComponente->getPK(
        ).'"}]';
        $data = [
            'pertenece_nucleo' => 0,
            'nombre'           => PqrFormEntity::NOMBRE_REPORTE_POR_DEPENDENCIA,
            'tipo'             => Modulo::TIPO_HIJO,
            'imagen'           => 'fa fa-bar-chart-o',
            'etiqueta'         => 'Por Dependencia',
            'enlace'           => $enlace,
            'cod_padre'        => $ModuloPadre->getPK(),
            'orden'            => 4,
            'asignable'        => 1,
            'tiene_hijos'      => 0,
        ];

        $ModuloService = (new Modulo())->getService();
        if (!$ModuloService->save($data)) {
            $trans = $this->serviceLocator->getTranslator()->trans("no_fue_posible_registrar_reporte_pqr");

            throw new RuntimeException($trans);
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
        return $this->pqrFormFieldRepository->getDataAttributesForForm($this->entity->getId());
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
        return $this->entity->toArray();
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
        $notifications = $this->getPqrNotificationRepository()->findByPqrForm($this->entity->getId());

        return array_map(static fn ($n) => [
            'id'            => $n->getId(),
            'fk_funcionario' => $n->getFkFuncionario(),
            'fk_pqr_form'   => $n->getFkPqrForm(),
            'email'         => (int)$n->isEmail(),
            'notify'        => (int)$n->isNotify(),
        ], $notifications);
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
        return $this->pqrFormFieldRepository->getReportFieldsData($this->entity->getId());
    }

    /**
     * Obtiene los campos para crear la vista
     *
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2021-06-30
     */
    private function getFieldsView(): array
    {
        return array_map(
            fn ($f) => "ft.{$f->getName()}",
            $this->pqrFormFieldRepository->findByPqrFormOrdered($this->entity->getId()),
        );
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
        $fields = implode(
            ',',
            array_merge(
                $this->defaultFieldsReport(),
                $this->getFieldsView(),
            ),
        );

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
            'ft.idft_pqr as idft',
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
            $pqrFormFieldEntity = $this->pqrFormFieldRepository->find($pqrFormId);
            $data = [
                "id"   => $pqrFormId,
                "name" => $pqrFormFieldEntity?->getLabel() ?? '',
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
        foreach ($fields as $f) {
            $code = '';
            switch ($f['type_saia']) {
                // case 'Textarea':
                //     $code = "function get_{$f['name']}(int \$idft,\$value){
                //         return substr(\$value, 0, 30).' ...';
                //     }";
                //     break;
                case 'Select':
                case 'Radio':
                    $code = "function get_{$f['name']}(int \$idft,\$value){
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
                    $code = "function get_{$f['name']}(int \$idft,\$value){
                        global \$FtPqr;
                        \$response = '';
                        if (\$valor = Saia\\models\\formatos\\CampoSeleccionados::findColumn('valor', [
                            'fk_campos_formato' => {$f['fk_campos_formato']},
                            'fk_documento' => \$FtPqr->documento_iddocumento
                        ])) {
                            \$response = implode(',',\$valor);
                        }
                        return \$response;
                    }";
                    break;
                case 'AutocompleteM':
                case 'AutocompleteD':
                    $code = "function get_{$f['name']}(int \$idft,\$value){
                        global \$FtPqr;
                        return \$FtPqr->getService()->getValueForReport('{$f['name']}');
                    }";
                    break;
                case 'Date':
                    $code = "function get_{$f['name']}(int \$idft,\$value){
                          return \$value ? dateRadication(\$value) : '';
                    }";
                    break;
            }
            if ($code) {
                $fieldCode[] = $code;
            }
        }
        $file = $_SERVER["ROOT_PATH"].'src/Bundles/pqr/formatos/pqr/functionsReport.php';
        if (file_exists($file)) {
            unlink($file);
        }
        $codeFunction = "<?php \n\n".implode("\n", $fieldCode);

        if (!file_put_contents($file, $codeFunction)) {
            $trans = $this->serviceLocator->getTranslator()->trans("no_fue_posible_crear_funciones_formulario");
            throw new RuntimeException($trans);
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
            $nameOfSeletedFields[] = $PqrFormField['name'];
            $type = $PqrFormField['type_saia'];
            $selectedFields[] = match ($type) {
                'Text', 'Textarea' => [
                    'title' => strtoupper($PqrFormField['label']),
                    'field' => "{*{$PqrFormField['name']}*}",
                    'align' => 'center',
                ],
                default => [
                    'title' => strtoupper($PqrFormField['label']),
                    'field' => "{*get_{$PqrFormField['name']}@idft,{$PqrFormField['name']}*}",
                    'align' => 'center',
                ],
            };
        }

        //REPORTE PENDIENTE
        if ($Pendiente = BusquedaComponente::findByAttributes([
            'nombre' => PqrFormEntity::NOMBRE_REPORTE_PENDIENTE,
        ])) {
            $Pendiente->setAttributes(
                $this->getDefaultDataComponente(
                    $selectedFields,
                    $nameOfSeletedFields,
                    PqrFormEntity::NOMBRE_REPORTE_PENDIENTE,
                ),
            );
            $Pendiente->save();
        }

        //REPORTE PROCESO
        if ($Proceso = BusquedaComponente::findByAttributes([
            'nombre' => PqrFormEntity::NOMBRE_REPORTE_PROCESO,
        ])) {
            $Proceso->setAttributes(
                $this->getDefaultDataComponente(
                    $selectedFields,
                    $nameOfSeletedFields,
                    PqrFormEntity::NOMBRE_REPORTE_PROCESO,
                ),
            );
            $Proceso->save();
        }

        //REPORTE TERMINADO
        if ($Terminado = BusquedaComponente::findByAttributes([
            'nombre' => PqrFormEntity::NOMBRE_REPORTE_TERMINADO,
        ])) {
            $Terminado->setAttributes(
                $this->getDefaultDataComponente(
                    $selectedFields,
                    $nameOfSeletedFields,
                    PqrFormEntity::NOMBRE_REPORTE_TERMINADO,
                ),
            );
            $Terminado->save();
        }

        //REPORTE TODOS
        if ($Todos = BusquedaComponente::findByAttributes([
            'nombre' => PqrFormEntity::NOMBRE_REPORTE_TODOS,
        ])) {
            $Todos->setAttributes(
                $this->getDefaultDataComponente(
                    $selectedFields,
                    $nameOfSeletedFields,
                    PqrFormEntity::NOMBRE_REPORTE_TODOS,
                ),
            );
            $Todos->save();
        }
    }


    /**
     * Obtiene los campos y el info por defecto
     * de los reportes (busqueda componente)
     *
     * @param array $selectedFields
     * @param array $nameOfSeletedFields
     * @param string $reportName
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    private function getDefaultDataComponente(
        array $selectedFields,
        array $nameOfSeletedFields,
        string $reportName,
    ): array {
        $info = array_merge(
            [
                [
                    'title' => 'RADICADO',
                    'field' => '{*viewFtPqr@idft,numero*}',
                    'align' => 'center',
                ],
                [
                    'title' => 'FECHA',
                    'field' => '{*dateRadication@fecha*}',
                    'align' => 'center',
                ],
            ],
            $selectedFields,
            [
                [
                    'title' => 'TIPO',
                    'field' => '{*getValueSysTipo@iddocumento,sys_tipo*}',
                    'align' => 'center',
                ],
                [
                    'title' => 'OPORTUNIDAD EN LAS RESPUESTAS',
                    'field' => '{*sys_oportuno*}',
                    'align' => 'center',
                ],
                [
                    'title' => 'CANAL DE RECEPCIÓN',
                    'field' => '{*canal_recepcion*}',
                    'align' => 'center',
                ],
            ],
            $this->getFieldsByStateForReport($reportName),
            [
                [
                    'title' => 'OPCIONES',
                    'field' => '{*options@iddocumento,sys_estado,idft*}',
                    'align' => 'center',
                ],
            ],
        );

        $fieldNames = array_merge([
            'v.numero',
            'v.fecha',
            'v.sys_tipo',
            'v.sys_estado',
            'v.idft',
            'v.sys_oportuno',
            'v.canal_recepcion',
        ], $nameOfSeletedFields);

        return [
            'info'               => json_encode($info),
            'campos_adicionales' => implode(',', $fieldNames),
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
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2024-02-21
     */
    private function viewPqrTarea(): void
    {
        $tRelacion = Tarea::RELACION_DOCUMENTO;
        $teEstados = implode(',', [
            TareaEstado::PENDIENTE,
            TareaEstado::PROCESO,
            TareaEstado::DEVUELTA,
        ]);
        $tfTipo = TareaFuncionario::TYPE_MANAGER;
        $tfExterno = TareaFuncionario::INTERNAL_USER;

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
        $records = $this->pqrFormFieldRepository->findByPqrFormOrdered($this->entity->getId());

        foreach ($records as $pqrFormField) {
            $htmlField = $pqrFormField->getHtmlField();

            if (
                $htmlField->isValidFieldForResponseDaysOrBalance() &&
                $pqrFormField->isActive() &&
                $pqrFormField->getFkCamposFormato()
            ) {
                $fieldOptions = [];

                if ($pqrFormField->getName() !== PqrFormFieldEntity::FIELD_NAME_SYS_TIPO) {
                    $options = (new CamposFormato($pqrFormField->getFkCamposFormato()))->getCampoOpciones(['estado' => 1]);
                    foreach ($options as $CampoOpcion) {
                        if ($CampoOpcion->estado) {
                            $fieldOptions[] = [
                                'id'    => $CampoOpcion->getPK(),
                                'label' => $CampoOpcion->valor,
                            ];
                        }
                    }
                }

                $data[] = [
                    'id'      => $pqrFormField->getFkCamposFormato(),
                    'label'   => $pqrFormField->getLabel(),
                    'options' => $fieldOptions,
                ];
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
            'fk_field_time' => $idCampoFormato,
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
                'align' => 'center',
            ],
            [
                'title' => 'RESPONSABLES',
                'field' => '{*getResponsible@iddocumento*}',
                'align' => 'center',
            ],
            [
                'title' => 'RESPUESTAS',
                'field' => '{*totalAnswers@idft*}',
                'align' => 'center',
            ],
            [
                'title' => 'CALIFICACIÓN GESTIÓN',
                'field' => '{*qualificationGest@idft*}',
                'align' => 'center',
            ],
            [
                'title' => 'CALIFICACIÓN SERVICIO',
                'field' => '{*qualificationServ@idft*}',
                'align' => 'center',
            ],
        ];

        $otherDefaultFields = [
            [
                'title' => 'DIAS DE ESPERA',
                'field' => '{*getDaysWait@idft*}',
                'align' => 'center',
            ],
            [
                'title' => 'FECHA VENCIMIENTO',
                'field' => '{*getExpiration@idft*}',
                'align' => 'center',
            ],
        ];

        $fieldForReport = match ($reportName) {
            PqrFormEntity::NOMBRE_REPORTE_TODOS => array_merge([
                [
                    'title' => 'ESTADO',
                    'field' => '{*sys_estado*}',
                    'align' => 'center',
                ],
            ], $otherDefaultFields, $defaultFiels),
            PqrFormEntity::NOMBRE_REPORTE_PROCESO => array_merge($otherDefaultFields, $defaultFiels),
            PqrFormEntity::NOMBRE_REPORTE_TERMINADO => array_merge([
                [
                    'title' => 'DÍAS RETRASO',
                    'field' => '{*getDaysLate@idft*}',
                    'align' => 'center',
                ],
                [
                    'title' => 'FECHA FINALIZACIÓN',
                    'field' => '{*getEndDate@idft*}',
                    'align' => 'center',
                ],
            ], $defaultFiels),
            default => $otherDefaultFields,
        };

        $FtClassName = (new Formato($this->entity->getFkFormato()))->getFtClass();
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
        if ($this->entity->getDescriptionField() && $this->entity->getDescriptionField() === $fieldId) {
            return true;
        }

        $pqrFormField = $this->pqrFormFieldRepository->find($fieldId);
        if (!$pqrFormField) {
            return false;
        }

        $CamposFormato = new CamposFormato($pqrFormField->getFkCamposFormato());
        $isDescription = $CamposFormato->isDescriptionField();

        if ($isDescription && $fieldId === $this->entity->getDescriptionField()) {
            return true;
        }

        if (!$isDescription) {
            $actionList = explode(',', $CamposFormato->acciones);
            $actionList[] = CamposFormato::ACTION_DESCRIPTION;

            $CamposFormato->getService()->save([
                'acciones' => implode(',', array_filter($actionList)),
            ]);
        }

        $currentDescField = $this->entity->getDescriptionField();
        if ($currentDescField) {
            $PqrFormFieldDes = $this->pqrFormFieldRepository->find($currentDescField);
            if ($PqrFormFieldDes) {
                $CamposFormatoOld = new CamposFormato($PqrFormFieldDes->getFkCamposFormato());
                $actionListOld = array_diff(
                    explode(',', $CamposFormatoOld->acciones),
                    [CamposFormato::ACTION_DESCRIPTION],
                );
                $CamposFormatoOld->getService()->save([
                    'acciones' => implode(',', array_filter($actionListOld)),
                ]);
            }
        }

        return $this->save([
            'description_field' => $fieldId,
        ]);
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return $this->em;
    }

    private function getPqrFormFieldRepository(): PqrFormFieldRepository
    {
        return $this->pqrFormFieldRepository;
    }

    private function getPqrFormRepository(): PqrFormRepository
    {
        return $this->pqrFormRepository;
    }

    private function getPqrNotificationRepository(): PqrNotificationRepository
    {
        return $this->em->getRepository(PqrNotificationEntity::class);
    }

    private function getDataPqrNotyMessages(): array
    {
        return array_map(static fn ($msg) => [
            'text'  => $msg->getLabel(),
            'value' => [
                'id'           => $msg->getId(),
                'description'  => $msg->getDescription(),
                'subject'      => $msg->getSubject(),
                'message_body' => $msg->getMessageBody(),
                'type'         => $msg->getType(),
            ],
        ], $this->getEntityManager()->getRepository(PqrNotyMessageEntity::class)->findBy(['active' => true]));
    }
}
