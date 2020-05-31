<?php

namespace Saia\Pqr\controllers;

use Exception;
use Saia\Pqr\models\PqrForm;
use Saia\core\DatabaseConnection;
use Saia\Pqr\models\PqrFormField;
use Saia\Pqr\controllers\services\PqrFormService;
use Saia\Pqr\controllers\addEditFormat\AddEditFormat;
use Saia\Pqr\controllers\addEditFormat\IAddEditFormat;
use Saia\Pqr\controllers\addEditFormat\TAddEditFormat;
use Saia\Pqr\controllers\addEditFormat\FtPqrController;


class PqrFormController extends Controller
{
    use TAddEditFormat;

    const  DIRECTORY_PQR = 'ws/pqr/';
    const DIRECTORY_CLASIFICACION = 'ws/calificacion/';

    /**
     * Obtiene todos los datos del modulo de configuracion
     *
     * @return object
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function getSetting(): object
    {
        $Response = (object) [
            'success' => 1,
            'data' => [
                'urlWs' => PROTOCOLO_CONEXION . DOMINIO . '/' . self::DIRECTORY_PQR
            ]
        ];

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

            if ($PqrForm = PqrForm::getPqrFormActive()) {
                $PqrForm->setAttributes($this->request['pqrForm']);
                if (!$PqrForm->update()) {
                    throw new \Exception("No fue posible actualizar", 200);
                };
            } else {
                throw new \Exception("No se encontro un formulario activo");
            }

            if ($PqrForm->show_anonymous) {
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

            $PqrFormService = new PqrFormService($PqrForm);
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


    /*-------------------------------------------- */

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

            $this->PqrForm = PqrForm::getPqrFormActive();

            $option = $this->PqrForm->fk_formato ? AddEditFormat::ADIT : AddEditFormat::ADD;
            $this->addEditFormat(
                new FtPqrController($this->PqrForm),
                $option
            );

            // if (!$FormatoR = Formato::findByAttributes([
            //     'nombre' => 'pqr_respuesta'
            // ])) {
            //     throw new Exception("El formato de respuesta PQR no fue encontrado", 1);
            // }
            // $this->FormatGenerator($FormatoR->getPK());

            // if (!$FormatoC = Formato::findByAttributes([
            //     'nombre' => 'pqr_calificacion'
            // ])) {
            //     throw new Exception("El formato de calificacion PQR no fue encontrado", 1);
            // }
            // $this->FormatGenerator($FormatoC->getPK());

            // $this->generateView();

            // $Web = new WebservicePqr($this->PqrForm);
            // $Web->generate();

            // $WebCal = new WebserviceCalificacion($FormatoC);
            // $WebCal->generate();

            $Response->data = $this->PqrForm->getAttributes();
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
     * Encargado de genera el formato recibido
     *
     * @param IAddEditFormat $Controller
     * @param string $addEdit
     * @return void
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    protected function addEditFormat(IAddEditFormat $Controller, string $addEdit): void
    {
        $Generate = new AddEditFormat($Controller, $addEdit);
        $Generate->generate();
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
    protected function viewPqr()
    {
        $sql = "SELECT d.iddocumento,d.numero,d.fecha,ft.sys_email,ft.sys_tipo,ft.sys_estado,ft.idft_pqr as idft
        FROM ft_pqr ft,documento d
        WHERE ft.documento_iddocumento=d.iddocumento
        AND d.estado NOT IN ('ELIMINADO','ANULADO')";

        $this->createView('vpqr', $sql);
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
                throw new Exception("No fue posible generar la vista {$name}", 1);
                break;
        }
    }
}
