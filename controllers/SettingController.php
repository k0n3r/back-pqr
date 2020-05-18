<?php

namespace Saia\Pqr\controllers;

use Saia\core\DatabaseConnection;
use Saia\Pqr\controllers\Controller;
use Saia\Pqr\controllers\PqrFormController;
use Saia\Pqr\controllers\PqrFormFieldController;
use Saia\Pqr\models\PqrForm;
use Saia\Pqr\models\PqrFormField;

class SettingController extends Controller
{
    const  DIRECTORY_PQR = 'ws/pqr/';
    const DIRECTORY_CLASIFICACION = 'ws/calificacion/';

    public function __construct(array $request = null)
    {
        $this->request = $request;
    }

    public function getSetting(): object
    {
        $dataForm = PqrFormController::index();
        $dataFields = PqrFormFieldController::index();

        $Response = (object) [
            'success' => 1,
            'data' => [
                'formName' => $dataForm->data['label'],
                'showAnonymous' => (int) $dataForm->data['show_anonymous'],
                'showLabel' => (int) $dataForm->data['show_label'],
                'formfields' => $dataFields->data,
                'urlWs' => PROTOCOLO_CONEXION . DOMINIO . '/' . self::DIRECTORY_PQR,
            ]
        ];

        return $Response;
    }

    public function updateSetting(): object
    {
        $Response = (object) [
            'success' => 0
        ];

        try {
            $conn = DatabaseConnection::beginTransaction();
            PqrFormField::executeUpdate([
                'anonymous' => 0,
                'required_anonymous' => 0
            ], [
                'anonymous' => 1
            ]);

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

            $Response = $this->getSetting();
            $conn->commit();
        } catch (\Exception $th) {
            $conn->rollBack();
            $Response->message = $th->getMessage();
        }

        return $Response;
    }
}
