<?php

namespace Saia\Pqr\controllers;

use Exception;
use Saia\Pqr\models\PqrForm;
use Saia\core\DatabaseConnection;
use Saia\models\formatos\Formato;
use Saia\Pqr\models\PqrFormField;
use Saia\controllers\generator\FormatGenerator;
use Saia\Pqr\controllers\services\PqrFormService;
use Saia\Pqr\controllers\addEditFormat\AddEditFtPqr;
use Saia\Pqr\controllers\addEditFormat\IAddEditFormat;


class PqrFormController extends Controller
{
    const  DIRECTORY_PQR = 'ws/pqr/';
    const DIRECTORY_CLASIFICACION = 'ws/calificacion/';

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
        $PqrForm = PqrForm::getPqrFormActive();
        $PqrFormService = new PqrFormService($PqrForm);

        $Response = (object) [
            'success' => 1,
            'data' => [
                'urlWs' => PROTOCOLO_CONEXION . DOMINIO . '/' . self::DIRECTORY_PQR,
                'publish' => $this->PqrForm->fk_formato ? 1 : 0,
                'pqrForm' => $PqrFormService->getDataPqrForm(),
                'pqrTypes' => $PqrFormService->getTypes(),
                'pqrFormFields' => $PqrFormService->getDataPqrFormFields()
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

            AddEditFtPqr::addEditformatOptions($PqrFormField);

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

            DatabaseConnection::getQueryBuilder()
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

            $this->addEditFormat(
                new AddEditFtPqr($this->PqrForm)
            );

            if (!$FormatoR = Formato::findByAttributes([
                'nombre' => 'pqr_respuesta'
            ])) {
                throw new Exception("El formato de respuesta PQR no fue encontrado", 1);
            }
            $this->generateForm($FormatoR->getPK());

            if (!$FormatoC = Formato::findByAttributes([
                'nombre' => 'pqr_calificacion'
            ])) {
                throw new Exception("El formato de calificacion PQR no fue encontrado", 1);
            }
            $this->generateForm($FormatoC->getPK());

            $this->generateView();

            // $Web = new WebservicePqr($this->PqrForm);
            // $Web->generate();

            // $WebCal = new WebserviceCalificacion($FormatoC);
            // $WebCal->generate();

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
            $this->generateForm($this->PqrForm->fk_formato);
    }

    protected function generateForm(int $idformato): bool
    {
        $FormatGenerator = new FormatGenerator($idformato);
        $FormatGenerator->generate();
        $FormatGenerator->createModule();

        return true;
    }

    /**
     * Genera las vista del proceso
     *
     * @return void
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    protected function generateView(): void
    {
        $this->viewPqr();
        $this->viewRespuestaPqr();
    }

    /**
     * Genera el SQL de la vista PQR
     *
     * @return void
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function viewPqr(array $fields = [])
    {
        $fields = implode(',', array_merge($this->defaultFieldsReport(), $fields));

        $sql = "SELECT {$fields}
        FROM ft_pqr ft,documento d
        WHERE ft.documento_iddocumento=d.iddocumento
        AND d.estado NOT IN ('ELIMINADO','ANULADO')";

        $this->createView('vpqr', $sql);
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
}
