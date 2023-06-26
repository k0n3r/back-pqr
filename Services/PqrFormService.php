<?php

namespace App\Bundles\pqr\Services;

use App\services\exception\SaiaException;
use App\services\GlobalContainer;
use App\services\models\ModelService\ModelService;
use Saia\core\db\customDrivers\OtherQueriesForPlatform;
use Saia\models\formatos\Formato;
use App\Bundles\pqr\Services\models\PqrForm;
use Saia\models\busqueda\BusquedaComponente;
use Saia\controllers\generator\FormatGenerator;
use App\Bundles\pqr\Services\models\PqrFormField;
use Saia\controllers\generator\webservice\WsGenerator;
use App\Bundles\pqr\Services\controllers\WebserviceCalificacion;
use App\Bundles\pqr\Services\controllers\AddEditFormat\AddEditFtPqr;
use App\Bundles\pqr\Services\controllers\AddEditFormat\IAddEditFormat;
use Saia\models\Modulo;

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
     * Ruta del Ws de la Calificacion de la PQR
     *
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com> @date 2021-02-25
     */
    public static function getUrlWsCalificacion(): string
    {
        return $_SERVER['APP_DOMAIN'] . 'ws/pqr_calificacion/index.html';
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
        return [
            'urlWs'               => static::getUrlWsPQR(),
            'publish'             => $this->getModel()->fk_formato ? 1 : 0,
            'pqrForm'             => $this->getDataPqrForm(),
            'pqrFormFields'       => $this->getDataPqrFormFields(),
            'pqrNotifications'    => $this->getDataPqrNotifications(),
            'optionsNotyMessages' => PqrNotyMessageService::getDataPqrNotyMessages(),
            'responseTimeOptions' => $this->getDataresponseTime()
        ];
    }

    /**
     * Actualiza los dias de vencimientos de los tipo de PQR
     *
     * @param array $data
     * @return boolean
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function updatePqrTypes(array $data): bool
    {

        $PqrFormFieldService = ($this->getModel()->getRow(PqrFormField::FIELD_NAME_SYS_TIPO))->getService();
        if (!$PqrFormFieldService->update([
            'setting' => $data
        ])) {
            $this->getErrorManager()->setMessage("No fue posible actualizar los tipos");
            return false;
        }

        if ($PqrFormFieldService->getModel()->fk_campos_formato) {
            $PqrFormFieldService->addEditformatOptions();
        }

        return true;
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
        if (!$this->addEditFormat(
            new AddEditFtPqr($this->getModel())
        )) {
            $this->getErrorManager()->setMessage("No fue posible generar el formulario");
            return false;
        }

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

        if (!$this->generateForm($FormatoR)) {
            $this->getErrorManager()->setMessage("No fue posible generar el formulario: $formatNameR ");
            return false;
        }

        if (!$FormatoC = Formato::findByAttributes([
            'nombre' => 'pqr_calificacion'
        ])) {
            $this->getErrorManager()->setMessage("El formato de calificacion PQR no fue encontrado");
            return false;
        }

        $formatNameC = "CALIFICACIÓN ({$this->getModel()->label})";
        if ($FormatoC->etiqueta != $formatNameC) {
            $FormatoC->etiqueta = $formatNameC;
            $FormatoC->save();
        }

        if (!$this->generateForm($FormatoC)) {
            $this->getErrorManager()->setMessage("No fue posible generar el formulario: $FormatoC->etiqueta ");
            return false;
        }

        $this->generaReport();
        $this->viewRespuestaPqr();
        $this->viewCalificacionPqr();

        PqrService::activeGraphics();
        $this->activeInfoForDependency();

        if (!$this->generatePqrWs()) {
            $this->getErrorManager()->setMessage("No fue posible generar el Ws");
            return false;
        }

        if (!$this->generateCalificacionWs($FormatoC)) {
            $this->getErrorManager()->setMessage("No fue posible generar el Ws Calificacion");
            return false;
        }

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
     * Genera el formulario recibido
     *
     * @param IAddEditFormat $Instance
     * @return boolean
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    private function addEditFormat(IAddEditFormat $Instance): bool
    {
        return $Instance->updateChange() &&
            $this->generateForm($Instance->getFormat());
    }

    /**
     * Genera el Formato
     *
     * @param Formato $Formato
     * @return boolean
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2021
     */
    private function generateForm(Formato $Formato): bool
    {
        $FormatGenerator = new FormatGenerator($Formato);
        $FormatGenerator->generate();

        return true;
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
        AND d.estado NOT IN ('ELIMINADO','ANULADO')";

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
            'ft.sys_estado',
            'ft.sys_fecha_vencimiento',
            'ft.sys_fecha_terminado',
            'ft.sys_frecuencia',
            'ft.sys_impacto',
            'ft.sys_severidad',
            'ft.idft_pqr as idft',
            'ft.sys_oportuno'
        ];
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
        $code = $nameFields = [];
        foreach ($fields as $PqrFormField) {
            $nameFields[] = $PqrFormField->name;
            $type = $PqrFormField->getPqrHtmlField()->type_saia;
            switch ($type) {
                case 'Text':
                case 'Textarea':
                    $code[] = '{"title":"' . strtoupper($PqrFormField->label) . '","field":"{*' . $PqrFormField->name . '*}","align":"center"}';
                    break;
                default:
                    $code[] = '{"title":"' . strtoupper($PqrFormField->label) . '","field":"{*get_' . $PqrFormField->name . '@idft,' . $PqrFormField->name . '*}","align":"center"}';
                    break;
            }
        }

        //REPORTE PENDIENTE
        if ($Pendiente = BusquedaComponente::findByAttributes([
            'nombre' => PqrForm::NOMBRE_REPORTE_PENDIENTE
        ])) {
            $Pendiente->setAttributes(
                $this->getDefaultDataComponente($code, $nameFields, PqrForm::NOMBRE_REPORTE_PENDIENTE)
            );
            $Pendiente->save();
        }

        //REPORTE PROCESO
        if ($Proceso = BusquedaComponente::findByAttributes([
            'nombre' => PqrForm::NOMBRE_REPORTE_PROCESO
        ])) {
            $Proceso->setAttributes(
                $this->getDefaultDataComponente($code, $nameFields, PqrForm::NOMBRE_REPORTE_PROCESO)
            );
            $Proceso->save();
        }

        //REPORTE TERMINADO
        if ($Terminado = BusquedaComponente::findByAttributes([
            'nombre' => PqrForm::NOMBRE_REPORTE_TERMINADO
        ])) {
            $Terminado->setAttributes(
                $this->getDefaultDataComponente($code, $nameFields, PqrForm::NOMBRE_REPORTE_TERMINADO)
            );
            $Terminado->save();
        }

        //REPORTE TODOS
        if ($Todos = BusquedaComponente::findByAttributes([
            'nombre' => PqrForm::NOMBRE_REPORTE_TODOS
        ])) {
            $Todos->setAttributes(
                $this->getDefaultDataComponente($code, $nameFields, PqrForm::NOMBRE_REPORTE_TODOS)
            );
            $Todos->save();
        }

    }


    /**
     * Obtiene los campos y el info por defecto
     * de los reportes (busqueda componente)
     *
     * @param array  $infoFields
     * @param array  $nameFields
     * @param string $nameReport
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    private function getDefaultDataComponente(
        array $infoFields,
        array $nameFields,
        string $nameReport
    ): array {

        $aditionalInfo = '';
        if ($infoFields) {
            $aditionalInfo = implode(',', $infoFields) . ',';
        }

        $otherFields = '';
        if ($nameFields) {
            $otherFields .= "," . implode(',', $nameFields);
        }

        switch ($nameReport) {
            case PqrForm::NOMBRE_REPORTE_TODOS:
                $NewField = '{"title":"ESTADO","field":"{*sys_estado*}","align":"center"},{"title":"DIAS DE ESPERA","field":"{*getDaysWait@idft*}","align":"center"},{"title":"FECHA VENCIMIENTO","field":"{*getExpiration@idft*}","align":"center"},{"title":"TAREAS","field":"{*totalTask@iddocumento*}","align":"center"},{"title":"RESPONSABLES","field":"{*getResponsible@iddocumento*}","align":"center"},{"title":"RESPUESTAS","field":"{*totalAnswers@idft*}","align":"center"},{"title":"CALIFICACIÓN GESTIÓN","field":"{*qualificationGest@idft*}","align":"center"},{"title":"CALIFICACIÓN SERVICIO","field":"{*qualificationServ@idft*}","align":"center"},';
                break;
            case PqrForm::NOMBRE_REPORTE_PROCESO:
                $NewField = '{"title":"DIAS DE ESPERA","field":"{*getDaysWait@idft*}","align":"center"},{"title":"FECHA VENCIMIENTO","field":"{*getExpiration@idft*}","align":"center"},{"title":"TAREAS","field":"{*totalTask@iddocumento*}","align":"center"},{"title":"RESPONSABLES","field":"{*getResponsible@iddocumento*}","align":"center"},{"title":"RESPUESTAS","field":"{*totalAnswers@idft*}","align":"center"},{"title":"CALIFICACIÓN GESTIÓN","field":"{*qualificationGest@idft*}","align":"center"},{"title":"CALIFICACIÓN SERVICIO","field":"{*qualificationServ@idft*}","align":"center"},';
                break;
            case PqrForm::NOMBRE_REPORTE_TERMINADO:
                $NewField = '{"title":"FECHA FINALIZACIÓN","field":"{*getEndDate@idft*}","align":"center"},{"title":"DÍAS RETRASO","field":"{*getDaysLate@idft*}","align":"center"},{"title":"TAREAS","field":"{*totalTask@iddocumento*}","align":"center"},{"title":"RESPONSABLES","field":"{*getResponsible@iddocumento*}","align":"center"},{"title":"RESPUESTAS","field":"{*totalAnswers@idft*}","align":"center"},{"title":"CALIFICACIÓN GESTIÓN","field":"{*qualificationGest@idft*}","align":"center"},{"title":"CALIFICACIÓN SERVICIO","field":"{*qualificationServ@idft*}","align":"center"},';
                break;
            case PqrForm::NOMBRE_REPORTE_PENDIENTE:
            default:
                $NewField = '{"title":"DIAS DE ESPERA","field":"{*getDaysWait@idft*}","align":"center"},{"title":"FECHA VENCIMIENTO","field":"{*getExpiration@idft*}","align":"center"},';
                break;
        }

        return [
            'info'               => '[{"title":"RADICADO","field":"{*viewFtPqr@idft,numero*}","align":"center"},{"title":"FECHA","field":"{*dateRadication@fecha*}","align":"center"},' . $aditionalInfo . '{"title":"TIPO","field":"{*getValueSysTipo@iddocumento,sys_tipo*}","align":"center"},{"title":"OPORTUNIDAD EN LAS RESPUESTAS","field":"{*sys_oportuno*}","align":"center"},' . $NewField . '{"title":"OPCIONES","field":"{*options@iddocumento,sys_estado,idft*}","align":"center"}]',
            'campos_adicionales' => 'v.numero,v.fecha,v.sys_tipo,v.sys_estado,v.idft,v.sys_oportuno' . $otherFields
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
     * Genera el WS de PQR
     *
     * @return boolean
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    private function generatePqrWs(): bool
    {
        $folder = 'src/Bundles/pqr/Services/controllers/templates/';
        $page404 = WsGenerator::generateFileForWs('src/legacy/controllers/generator/webservice/templates/404.html');
        $infoQrFile = WsGenerator::generateFileForWs($folder . 'infoQR.html');
        $infoQRJsFile = WsGenerator::generateFileForWs($folder . 'infoQR.js');
        $timelineFile = WsGenerator::generateFileForWs($folder . 'TimeLine.js');

        $IWsHtml = $this->getModel()->getWebservicePqr();
        $WsGenerator = new WsGenerator(
            $IWsHtml,
            $this->getModel()->getFormatoFk()->nombre,
            false
        );

        $WsGenerator->addFiles([$infoQrFile, $infoQRJsFile, $timelineFile, $page404]);

        return $WsGenerator->create();
    }

    /**
     * Genera el WS de Calificacion PQR
     *
     * @param Formato $FormatoC
     * @return boolean
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    private function generateCalificacionWs(Formato $FormatoC): bool
    {
        $page404 = WsGenerator::generateFileForWs('src/legacy/controllers/generator/webservice/templates/404.html');

        $IWsHtml = new WebserviceCalificacion($FormatoC);
        $WsGenerator = new WsGenerator(
            $IWsHtml,
            $FormatoC->nombre,
            false
        );
        $WsGenerator->addFiles([$page404]);

        return $WsGenerator->create();
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

                if ($PqrHtmlField->isValidFieldForResponseDays() &&
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
}
