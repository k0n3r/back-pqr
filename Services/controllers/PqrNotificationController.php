<?php

namespace App\Bundles\pqr\Services\controllers;

use Saia\core\DatabaseConnection;

use App\Bundles\pqr\Services\models\PqrForm;
use App\Bundles\pqr\Services\models\PqrNotification;

class PqrNotificationController extends Controller
{

    /**
     * Almacena una nueva persona a notificar
     *
     * @return object
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function store(): object
    {
        $Response = (object) [
            'success' => 0,
            'data' => []
        ];

        try {
            $conn = DatabaseConnection::getDefaultConnection();
            $conn->beginTransaction();

            $PqrNotification = new PqrNotification();
            $PqrNotification->setAttributes([
                'fk_funcionario' => $this->request['id'],
                'fk_pqr_form' => PqrForm::getPqrFormActive()->getPK(),
                'email' => 0,
                'notify' => 1
            ]);
            if ($PqrNotification->create()) {
                $Response->success = 1;
                $Response->data = $PqrNotification->getDataAttributes();
            }
            $conn->commit();
        } catch (\Exception $th) {
            $conn->rollBack();
            $Response->message = $th->getMessage();
        }
        return $Response;
    }

    /**
     * Edita una persona a notificacion
     *
     * @return object
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function update(): object
    {
        $Response = (object) [
            'success' => 0,
            'data' => []
        ];

        try {
            $conn = DatabaseConnection::getDefaultConnection();
            $conn->beginTransaction();

            $PqrNotification = new PqrNotification($this->request['id']);
            $PqrNotification->setAttributes($this->request['data']);
            if ($PqrNotification->update()) {
                $Response->success = 1;
                $Response->data = $PqrNotification->getDataAttributes();
            }
            $conn->commit();
        } catch (\Exception $th) {
            $conn->rollBack();
            $Response->message = $th->getMessage();
        }
        return $Response;
    }

    /**
     * Elimina el registro
     *
     * @return object
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function destroy(): object
    {
        $Response = (object) [
            'success' => 0
        ];

        try {
            $conn = DatabaseConnection::getDefaultConnection();
            $conn->beginTransaction();

            $PqrNotification = new PqrNotification($this->request['id']);
            if ($PqrNotification->delete()) {
                $Response->success = 1;
            }
            $conn->commit();
        } catch (\Exception $th) {
            $conn->rollBack();
            $Response->message = $th->getMessage();
        }
        return $Response;
    }
}
