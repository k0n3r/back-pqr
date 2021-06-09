<?php

namespace App\Bundles\pqr\Services;

use App\services\GlobalContainer;
use App\services\models\ModelService\ModelService;
use Exception;
use Saia\models\grafico\Grafico;
use Saia\models\formatos\Formato;
use Saia\models\grafico\PantallaGrafico;
use App\Bundles\pqr\Services\models\PqrForm;
use Saia\models\busqueda\BusquedaComponente;
use Saia\controllers\generator\FormatGenerator;
use App\Bundles\pqr\Services\models\PqrFormField;
use Saia\controllers\generator\webservice\WsGenerator;
use App\Bundles\pqr\Services\controllers\WebservicePqr;
use App\Bundles\pqr\Services\controllers\WebserviceCalificacion;
use App\Bundles\pqr\Services\controllers\AddEditFormat\AddEditFtPqr;
use App\Bundles\pqr\Services\controllers\AddEditFormat\IAddEditFormat;

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
                'name' => $name,
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
            'urlWs' => static::getUrlWsPQR(),
            'publish' => $this->getModel()->fk_formato ? 1 : 0,
            'pqrForm' => $this->getDataPqrForm(),
            'pqrFormFields' => $this->getDataPqrFormFields(),
            'pqrNotifications' => $this->getDataPqrNotifications(),
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
        if (!$this->getModel()->fk_formato) {
            PqrService::activeGraphics();
        }

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
        $fields = $this->getFieldsReport(true);
        $this->viewPqr();
        $this->generateFuncionReport($fields);
        $this->updateReport($fields);
    }

    /**
     * Obtiene los campos adicionales que seran cargado
     * en la vista y en el reporte
     *
     * @param boolean $instance :obtener instancia o campos
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    private function getFieldsReport(bool $instance = false): array
    {
        $data = [];
        $fields = $this->getModel()->getPqrFormFields();
        foreach ($fields as $PqrFormField) {
            if ($PqrFormField->show_report) {
                if ($instance) {
                    $data[] = $PqrFormField;
                } else {
                    $data[] = "ft.$PqrFormField->name";
                }
            }
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
            $this->getFieldsReport()
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
            'ft.sys_tipo',
            'ft.sys_estado',
            'ft.sys_fecha_vencimiento',
            'ft.sys_fecha_terminado',
            'ft.idft_pqr as idft'
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
        $Connection = GlobalContainer::getConnection();

        switch ($_SERVER['APP_DATABASE_DRIVER']) {
            case 'pdo_mysql':
            case 'oci8':
                $create = "CREATE OR REPLACE VIEW $name AS $select";
                $Connection->executeQuery($create);
                break;

            case 'pdo_sqlserver':
                $drop = "DROP VIEW $name";
                $Connection->executeQuery($drop);

                $create = "CREATE VIEW $name AS $select";
                $Connection->executeQuery($create);

                break;

            default:
                throw new Exception("No fue posible generar la vista $name", 200);
        }
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
            }
            if ($code) {
                $fieldCode[] = $code;
            }
        }
        $file = $_SERVER["ROOT_PATH"] . 'src/Bundles/pqr/formatos/pqr/functionsReport.php';
        if (file_exists($file)) {
            unlink($file);
        }
        $codeFunction = "<?php \n\n" . implode("\n", $fieldCode) . "\n ?>";

        if (!file_put_contents($file, $codeFunction)) {
            throw new Exception("No fue posible crear las funciones del formulario", 200);
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
        $sysDependencia = false;
        foreach ($fields as $PqrFormField) {
            $nameFields[] = $PqrFormField->name;
            if ($PqrFormField->name == 'sys_dependencia') {
                $sysDependencia = true;
            }
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
        if ($sysDependencia) {
            if (!$PantallaGrafico = PantallaGrafico::findByAttributes([
                'nombre' => PqrForm::NOMBRE_PANTALLA_GRAFICO
            ])) {
                throw new Exception("No se encuentra la pantalla de los grafico", 200);
            }

            $Grafico = Grafico::findByAttributes([
                'fk_pantalla_grafico' => $PantallaGrafico->getPK(),
                'nombre' => 'Dependencia'
            ]);
            $Grafico->estado = 1;
            $Grafico->save();
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
            case PqrForm::NOMBRE_REPORTE_PROCESO:
                $NewField = '{"title":"DIAS DE ESPERA","field":"{*getDaysWait@idft*}","align":"center"},{"title":"FECHA VENCIMIENTO","field":"{*getExpiration@idft*}","align":"center"},{"title":"TAREAS","field":"{*totalTask@iddocumento*}","align":"center"},{"title":"RESPONSABLES","field":"{*getResponsible@iddocumento*}","align":"center"},{"title":"RESPUESTAS","field":"{*totalAnswers@idft*}","align":"center"},';
                break;
            case PqrForm::NOMBRE_REPORTE_TERMINADO:
                $NewField = '{"title":"FECHA FINALIZACIÓN","field":"{*getEndDate@idft*}","align":"center"},{"title":"DÍAS RETRASO","field":"{*getDaysLate@idft*}","align":"center"},{"title":"TAREAS","field":"{*totalTask@iddocumento*}","align":"center"},{"title":"RESPONSABLES","field":"{*getResponsible@iddocumento*}","align":"center"},{"title":"RESPUESTAS","field":"{*totalAnswers@idft*}","align":"center"},';
                break;
            case PqrForm::NOMBRE_REPORTE_PENDIENTE:
            default:
                $NewField = '{"title":"DIAS DE ESPERA","field":"{*getDaysWait@idft*}","align":"center"},{"title":"FECHA VENCIMIENTO","field":"{*getExpiration@idft*}","align":"center"},';
                break;
        }

        return [
            'info' => '[{"title":"RADICADO","field":"{*viewFtPqr@idft,numero*}","align":"center"},{"title":"FECHA","field":"{*dateRadication@fecha*}","align":"center"},' . $aditionalInfo . '{"title":"TIPO","field":"{*getValueSysTipo@iddocumento,sys_tipo*}","align":"center"},' . $NewField . '{"title":"OPCIONES","field":"{*options@iddocumento,sys_estado,idft*}","align":"center"}]',
            'campos_adicionales' => 'v.numero,v.fecha,v.sys_tipo,v.sys_estado,v.idft' . $otherFields
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
        $sql = "SELECT d.iddocumento,d.numero,d.fecha,ft.idft_pqr_calificacion as idft,ft.ft_pqr_respuesta,ft.experiencia_gestion,ft.experiencia_servicio
        FROM ft_pqr_calificacion ft,documento d
        WHERE ft.documento_iddocumento=d.iddocumento AND d.estado NOT IN ('ELIMINADO')";

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

        $IWsHtml = new WebservicePqr($this->getModel()->getFormatoFk());
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
                        $options = $PqrFormField->getCamposFormato()->getCampoOpciones();
                        foreach ($options as $CampoOpcion) {
                            if ($CampoOpcion->estado) {
                                $fieldOptions[] = [
                                    'id' => $CampoOpcion->getPK(),
                                    'label' => $CampoOpcion->valor
                                ];
                            }
                        }
                    }

                    $data[] = [
                        'id' => $PqrFormField->fk_campos_formato,
                        'label' => $PqrFormField->label,
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
