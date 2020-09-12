<?php

namespace Saia\Pqr\controllers;

use Exception;
use Saia\Pqr\models\PqrForm;
use Doctrine\DBAL\Types\Type;
use Saia\models\grafico\Grafico;
use Saia\core\DatabaseConnection;
use Saia\models\formatos\Formato;
use Saia\Pqr\models\PqrFormField;
use Saia\controllers\SessionController;
use Saia\models\grafico\PantallaGrafico;
use Saia\models\busqueda\BusquedaComponente;
use Saia\controllers\generator\FormatGenerator;
use Saia\controllers\generator\webservice\WsFt;
use Saia\Pqr\controllers\services\PqrFormService;
use Saia\Pqr\controllers\addEditFormat\AddEditFtPqr;
use Saia\controllers\generator\webservice\WsGenerator;
use Saia\Pqr\controllers\addEditFormat\IAddEditFormat;


class PqrFormController extends Controller
{
    const URLWSPQR = PROTOCOLO_CONEXION . DOMINIO . '/' . CONTENEDOR_SAIA . '/ws/pqr/';
    const URLWSCALIFICACION = PROTOCOLO_CONEXION . DOMINIO . '/' . CONTENEDOR_SAIA . '/ws/pqr_calificacion/';

    private PqrForm $PqrForm;

    public function __construct(array $request = null)
    {
        parent::__construct($request);
        $this->PqrForm = PqrForm::getPqrFormActive();
    }

    /**
     * Obtiene todos los datos del modulo de configuracion
     *
     * @return object
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function getSetting(): object
    {
        $PqrFormService = new PqrFormService($this->PqrForm);

        $Response = (object) [
            'success' => 1,
            'data' => [
                'urlWs' => self::URLWSPQR,
                'publish' => $this->PqrForm->fk_formato ? 1 : 0,
                'pqrForm' => $PqrFormService->getDataPqrForm(),
                'pqrTypes' => $PqrFormService->getTypes(),
                'pqrFormFields' => $PqrFormService->getDataPqrFormFields(),
                'pqrNotifications' => $PqrFormService->getDataPqrNotifications()
            ]
        ];

        return $Response;
    }

    /**
     * Actualiza los dias de vencimientos de los tipo de PQR
     *
     * @return object
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function updatePqrTypes(): object
    {
        $Response = (object) [
            'success' => 0
        ];

        try {
            $conn = DatabaseConnection::getDefaultConnection();
            $conn->beginTransaction();

            $PqrFormField = $this->PqrForm->getRow('sys_tipo');
            $PqrFormField->setAttributes([
                'setting' => json_encode($this->request)
            ]);
            $PqrFormField->update();

            if ($PqrFormField->fk_campos_formato) {
                AddEditFtPqr::addEditformatOptions($PqrFormField);
            }

            $Response->success = 1;
            $conn->commit();
        } catch (\Exception $th) {
            $conn->rollBack();
            $Response->message = $th->getMessage();
        }

        return $Response;
    }

    /**
     * Habilita/deshabilita la radicacion por Email
     *
     * @return object
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function updatePqrForm(): object
    {
        $Response = (object) [
            'success' => 0
        ];

        try {
            $conn = DatabaseConnection::getDefaultConnection();
            $conn->beginTransaction();

            $this->PqrForm->setAttributes($this->request['pqrForm']);
            if (!$this->PqrForm->update()) {
                throw new \Exception("No fue posible actualizar", 200);
            };
            $PqrFormService = new PqrFormService($this->PqrForm);

            $Response->pqrForm = $PqrFormService->getDataPqrForm();
            $Response->success = 1;
            $conn->commit();
        } catch (\Exception $th) {
            $conn->rollBack();
            $Response->message = $th->getMessage();
        }

        return $Response;
    }

    /**
     * Actualiza los datos de configuracion del formulario
     *
     * @return object
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function updateSetting(): object
    {
        $Response = (object) [
            'success' => 0
        ];

        try {
            $conn = DatabaseConnection::getDefaultConnection();
            $conn->beginTransaction();

            DatabaseConnection::getDefaultConnection()
                ->createQueryBuilder()
                ->update('pqr_form_fields')
                ->set('anonymous', 0)
                ->set('required_anonymous', 0)
                ->where("name<>'sys_tipo'")->execute();


            $this->PqrForm->setAttributes($this->request['pqrForm']);
            if (!$this->PqrForm->update()) {
                throw new \Exception("No fue posible actualizar", 200);
            };

            if ($this->PqrForm->show_anonymous) {
                if ($formFields = $this->request['formFields']) {
                    foreach ($formFields['dataShowAnonymous'] as $id) {
                        $PqrFormField = new PqrFormField($id);
                        $PqrFormField->anonymous = 1;
                        if ($dataRequired = $formFields['dataRequiredAnonymous']) {
                            if (in_array($id, $dataRequired)) {
                                $PqrFormField->required_anonymous = 1;
                            }
                        }
                        if (!$PqrFormField->update()) {
                            throw new \Exception("No fue posible actualizar", 200);
                        };
                    }
                }
            }

            $PqrFormService = new PqrFormService($this->PqrForm);
            $Response->data = [
                'pqrForm' => $PqrFormService->getDataPqrForm(),
                'pqrFormFields' => $PqrFormService->getDataPqrFormFields(),
            ];
            $Response->success = 1;
            $conn->commit();
        } catch (\Exception $th) {
            $conn->rollBack();
            $Response->message = $th->getMessage();
        }

        return $Response;
    }

    /**
     * Actualiza la configuracion para la respuesta
     *
     * @return object
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function updateResponseConfiguration(): object
    {
        $Response = (object) [
            'success' => 0
        ];

        try {
            $conn = DatabaseConnection::getDefaultConnection();
            $conn->beginTransaction();
            $data = [];
            foreach ($this->request['tercero'] as $name => $value) {
                $data[] = [
                    'name' => $name,
                    'value' => $value
                ];
            }

            $this->PqrForm->response_configuration = json_encode(['tercero' => $data]);
            if (!$this->PqrForm->update()) {
                throw new \Exception("No fue posible actualizar", 200);
            };

            $Response->success = 1;
            $conn->commit();
        } catch (\Exception $th) {
            $conn->rollBack();
            $Response->message = $th->getMessage();
        }

        return $Response;
    }

    /**
     * Obtiene la configuracion de la respuesta
     *
     * @return object
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function getResponseConfiguration(): object
    {
        return (object) [
            'success' => 1,
            'data' => $this->PqrForm->getResponseConfiguration(true) ?? []
        ];
    }

    /**
     * publica o crea el formulario en el webservice
     *
     * @return object
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function publish(): object
    {
        $Response = (object) [
            'success' => 1,
            'data' => []
        ];

        try {
            $conn = DatabaseConnection::getDefaultConnection();
            $conn->beginTransaction();

            if (!$this->PqrForm->fk_formato) {
                $this->activeGraphics();
            }

            $this->addEditFormat(
                new AddEditFtPqr($this->PqrForm)
            );

            if (!$FormatoR = Formato::findByAttributes([
                'nombre' => 'pqr_respuesta'
            ])) {
                throw new Exception("El formato de respuesta PQR no fue encontrado", 1);
            }
            $formatNameR = "COMUNICACIÓN EXTERNA ({$this->PqrForm->label})";
            if ($FormatoR->etiqueta != $formatNameR) {
                $FormatoR->etiqueta = $formatNameR;
                $FormatoR->save();
            }
            $this->generateForm($FormatoR);

            if (!$FormatoC = Formato::findByAttributes([
                'nombre' => 'pqr_calificacion'
            ])) {
                throw new Exception("El formato de calificacion PQR no fue encontrado", 1);
            }
            $this->generateForm($FormatoC);

            $this->generaReport();
            $this->viewRespuestaPqr();
            $this->viewCalificacionPqr();

            $this->generatePqrWs();
            $this->generateCalificacionWs($FormatoC);

            $PqrFormService = new PqrFormService($this->PqrForm);
            $Response->data = [
                'pqrForm' => $PqrFormService->getDataPqrForm(),
                'pqrFormFields' => $PqrFormService->getDataPqrFormFields(),
            ];
            $conn->commit();
        } catch (\Throwable $th) {
            var_dump($th);
            $conn->rollBack();
            $Response->success = 0;
            $Response->message = $th->getMessage();
        }

        return $Response;
    }

    /**
     * Genera un archivo basado en el template recibido
     *
     * @param string $templateName
     * @param string $urlFolderTemplate
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    private function generateFile(string $templateName, string $urlFolderTemplate): string
    {
        global $rootPath;

        $values = [
            'baseUrl' => ABSOLUTE_SAIA_ROUTE
        ];

        $content = WsFt::getContent(
            "{$urlFolderTemplate}{$templateName}.php",
            $values
        );
        $fileName = SessionController::getTemporalDir() . "/{$templateName}";

        if (!file_put_contents($rootPath . $fileName, $content)) {
            throw new Exception("Imposible crear el archivo {$templateName} para el ws", 1);
        }

        return $fileName;
    }

    /**
     * Genera el WS de PQR
     *
     * @return boolean
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    private function generatePqrWs(): bool
    {

        $urlFolderTemplate = "app/modules/back_pqr/controllers/templates/";

        $defineFile = $this->generateFile('define.js', 'app/controllers/generator/webservice/templates/');
        $page404 = $this->generateFile('404.html', $urlFolderTemplate);
        $infoQrFile = $this->generateFile('infoQR.html', $urlFolderTemplate);
        $infoQRJsFile = $this->generateFile('infoQR.js', $urlFolderTemplate);
        $timelineFile = $this->generateFile('TimeLine.js', $urlFolderTemplate);

        $IWsHtml = new WebservicePqr($this->PqrForm->Formato);
        $WsGenerator = new WsGenerator(
            $IWsHtml,
            $this->PqrForm->Formato->nombre,
            false
        );

        $WsGenerator->loadAdditionalFiles([$defineFile]);
        $WsGenerator->addFiles([$infoQrFile, $infoQRJsFile, $timelineFile, $page404]);

        return $WsGenerator->create();
    }

    /**
     * Genera el WS de Calificacion PQR
     *
     * @return boolean
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    private function generateCalificacionWs(Formato $FormatoC): bool
    {
        $errorPage = $this->generateFile('404.html', 'app/modules/back_pqr/controllers/templates/');
        $fileName = $this->generateFile('define.js', 'app/controllers/generator/webservice/templates/');

        $IWsHtml = new WebserviceCalificacion($FormatoC);
        $WsGenerator = new WsGenerator(
            $IWsHtml,
            $FormatoC->nombre,
            false
        );
        $WsGenerator->addFiles([$errorPage]);
        $WsGenerator->loadAdditionalFiles([$fileName]);

        return $WsGenerator->create();
    }

    /**
     * Activa los indicadores preestablecidos
     *
     * @return void
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    protected function activeGraphics(): void
    {
        if (!$PantallaGrafico = PantallaGrafico::findByAttributes([
            'nombre' => PqrForm::NOMBRE_PANTALLA_GRAFICO
        ])) {
            throw new \Exception("No se encuentra la pantalla de los grafico", 200);
        }

        DatabaseConnection::getDefaultConnection()
            ->createQueryBuilder()
            ->update('grafico')
            ->set('estado', 1)
            ->where('fk_pantalla_grafico=:idpantalla')
            ->setParameter(':idpantalla', $PantallaGrafico->getPK(), Type::getType('integer'))
            ->andWhere("nombre<>'Dependencia'")
            ->execute();
    }

    /**
     * Genera el formulario recibido
     *
     * @param IAddEditFormat $Instance
     * @return boolean
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    protected function addEditFormat(IAddEditFormat $Instance): bool
    {
        return $Instance->updateChange() &&
            $this->generateForm($this->PqrForm->Formato);
    }

    protected function generateForm(Formato $Formato): bool
    {
        $FormatGenerator = new FormatGenerator($Formato);
        $FormatGenerator->generate();
        $FormatGenerator->createModule();

        return true;
    }

    /**
     * Genera el SQL de la vista PQR
     *
     * @return void
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function viewPqr()
    {
        $fields = implode(',', array_merge($this->defaultFieldsReport(), $this->getFieldsReport()));

        $sql = "SELECT {$fields}
        FROM ft_pqr ft,documento d
        WHERE ft.documento_iddocumento=d.iddocumento
        AND d.estado NOT IN ('ELIMINADO','ANULADO')";

        $this->createView('vpqr', $sql);
    }

    /**
     * Obtiene los campos adicionales que seran cargado
     * en la vista y en el reporte
     *
     * @param boolean $instance :obtener instancia o campos
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    private function getFieldsReport(bool $instance = false): array
    {
        $data = [];
        $fields = $this->PqrForm->PqrFormFields;
        foreach ($fields as $PqrFormField) {
            if ($PqrFormField->show_report) {
                if ($instance) {
                    $data[] = $PqrFormField;
                } else {
                    $data[] = "ft.{$PqrFormField->name}";
                }
            }
        }

        return $data;
    }

    private function defaultFieldsReport()
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
     * Genera el SQL de la vista respuesta a la PQR
     *
     * @return void
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    protected function viewRespuestaPqr()
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
     * @date 2020
     */
    protected function viewCalificacionPqr()
    {
        $sql = "SELECT d.iddocumento,d.numero,d.fecha,ft.idft_pqr_calificacion as idft,ft.ft_pqr_respuesta,ft.experiencia_gestion,ft.experiencia_servicio
        FROM ft_pqr_calificacion ft,documento d
        WHERE ft.documento_iddocumento=d.iddocumento AND d.estado NOT IN ('ELIMINADO')";

        $this->createView('vpqr_calificacion', $sql);
    }

    /**
     * Crea la vista en la DB
     *
     * @param string $select
     * @return void
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    protected function createView(string $name, string $select): void
    {
        $conn = DatabaseConnection::getInstance();

        switch (MOTOR) {
            case 'MySql':
            case 'Oracle':
                $create = "CREATE OR REPLACE VIEW {$name} AS {$select}";
                $conn->executeQuery($create);
                break;

            case 'SqlServer':
                $drop = "DROP VIEW {$name}";
                $conn->executeQuery($drop);

                $create = "CREATE VIEW {$name} AS {$select}";
                $conn->executeQuery($create);

                break;

            default:
                throw new \Exception("No fue posible generar la vista {$name}", 200);
                break;
        }
    }

    /**
     * Actualiza el reporte
     *
     * @return void
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function generaReport(): void
    {
        $fields = $this->getFieldsReport(true);
        $this->viewPqr();
        $this->generateFuncionReport($fields);
        $this->updateReport($fields);
    }

    /**
     * actualiza el reporte (busqueda componente)
     *
     * @param PqrFormField[] $fields
     * @return boolean
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    private function updateReport(array $fields): bool
    {
        $code = $nameFields = [];
        $sysDependencia = false;
        foreach ($fields as $PqrFormField) {
            $nameFields[] = $PqrFormField->name;
            if ($PqrFormField->name == 'sys_dependencia') {
                $sysDependencia = true;
            }
            $type = $PqrFormField->PqrHtmlField->type_saia;
            switch ($type) {
                case 'Text':
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
                throw new \Exception("No se encuentra la pantalla de los grafico", 200);
            }

            $Grafico = Grafico::findByAttributes([
                'fk_pantalla_grafico' => $PantallaGrafico->getPK(),
                'nombre' => 'Dependencia'
            ]);
            $Grafico->estado = 1;
            $Grafico->update();
        }

        //REPORTE PENDIENTE
        if ($Pendiente = BusquedaComponente::findByAttributes([
            'nombre' => PqrForm::NOMBRE_REPORTE_PENDIENTE
        ])) {
            $Pendiente->setAttributes(
                $this->getDefaultDataComponente($code, $nameFields, PqrForm::NOMBRE_REPORTE_PENDIENTE)
            );
            $Pendiente->update();
        }

        //REPORTE PROCESO
        if ($Proceso = BusquedaComponente::findByAttributes([
            'nombre' => PqrForm::NOMBRE_REPORTE_PROCESO
        ])) {
            $Proceso->setAttributes(
                $this->getDefaultDataComponente($code, $nameFields, PqrForm::NOMBRE_REPORTE_PROCESO)
            );
            $Proceso->update();
        }

        //REPORTE TERMINADO
        if ($Terminado = BusquedaComponente::findByAttributes([
            'nombre' => PqrForm::NOMBRE_REPORTE_TERMINADO
        ])) {
            $Terminado->setAttributes(
                $this->getDefaultDataComponente($code, $nameFields, PqrForm::NOMBRE_REPORTE_TERMINADO)
            );
            $Terminado->update();
        }

        //REPORTE TODOS
        if ($Todos = BusquedaComponente::findByAttributes([
            'nombre' => PqrForm::NOMBRE_REPORTE_TODOS
        ])) {
            $Todos->setAttributes(
                $this->getDefaultDataComponente($code, $nameFields, PqrForm::NOMBRE_REPORTE_TODOS)
            );
            $Todos->update();
        }

        return true;
    }

    /**
     * Obtiene los campos y el info por defecto
     * de los reportes (busqueda componente)
     *
     * @param array $infoFields
     * @param array $nameFields
     * @param string $nameReport
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
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
                $NewField = '{"title":"VENCIMIENTO","field":"{*getExpiration@idft*}","align":"center"},{"title":"TAREAS","field":"{*totalTask@iddocumento*}","align":"center"},{"title":"RESPUESTAS","field":"{*totalAnswers@idft*}","align":"center"},';
                break;
            case PqrForm::NOMBRE_REPORTE_TERMINADO:
                $NewField = '{"title":"FECHA FINALIZACIÓN","field":"{*getEndDate@idft*}","align":"center"},{"title":"DÍAS RETRASO","field":"{*getDaysLate@idft*}","align":"center"},{"title":"TAREAS","field":"{*totalTask@iddocumento*}","align":"center"},{"title":"RESPUESTAS","field":"{*totalAnswers@idft*}","align":"center"},';
                break;
            case PqrForm::NOMBRE_REPORTE_PENDIENTE:
            default:
                $NewField = '{"title":"VENCIMIENTO","field":"{*getExpiration@idft*}","align":"center"},';
                break;
        }

        return [
            'info' => '[{"title":"RADICADO","field":"{*viewFtPqr@idft,numero*}","align":"center"},{"title":"FECHA","field":"{*dateRadication@fecha*}","align":"center"},' . $aditionalInfo . '{"title":"TIPO","field":"{*getValueSysTipo@iddocumento,sys_tipo*}","align":"center"},' . $NewField . '{"title":"OPCIONES","field":"{*options@iddocumento,sys_estado,idft*}","align":"center"}]',
            'campos_adicionales' => 'v.numero,v.fecha,v.sys_tipo,v.sys_estado,v.idft' . $otherFields
        ];
    }


    /**
     * Genera el archivo de funciones para el reporte
     *
     * @param PqrFormField[] $fields
     * @return boolean
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    private function generateFuncionReport(array $fields): bool
    {
        global $rootPath;

        $fieldCode = [];
        foreach ($fields as $PqrFormField) {
            $code = '';
            switch ($PqrFormField->PqrHtmlField->type_saia) {
                case 'Textarea':
                    $code = "function get_{$PqrFormField->name}(int \$idft,\$value){
                        return substr(\$value, 0, 30).' ...';
                    }";
                    break;
                case 'Select':
                case 'Radio':
                    $code = "function get_{$PqrFormField->name}(int \$idft,\$value){
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
                    $code = "function get_{$PqrFormField->name}(int \$idft,\$value){
                        global \$FtPqr;
                        \$response = '';
                        if (\$valor = Saia\\models\\formatos\\CampoSeleccionados::findColumn('valor', [
                            'fk_campos_formato' => {$PqrFormField->fk_campos_formato},
                            'fk_documento' => \$FtPqr->documento_iddocumento
                        ])) {
                            \$response = implode(',',\$valor);
                        }
                        return \$response;
                    }";
                    break;
                case 'AutocompleteM':
                case 'AutocompleteD':
                    $code = "function get_{$PqrFormField->name}(int \$idft,\$value){
                        global \$FtPqr;
                        return \$FtPqr->getValueForReport('{$PqrFormField->name}');
                    }";
                    break;
            }
            if ($code) {
                $fieldCode[] = $code;
            }
        }
        $file = $rootPath . 'app/modules/back_pqr/formatos/pqr/functionsReport.php';
        if (file_exists($file)) {
            unlink($file);
        }
        $codeFunction = "<?php \n\n" . implode("\n", $fieldCode) . "\n ?>";

        if (!file_put_contents($file, $codeFunction)) {
            throw new \Exception("No fue posible crear las funciones del formulario", 200);
        }

        return true;
    }
}
