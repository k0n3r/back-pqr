<?php

namespace Saia\Pqr\controllers;

use Saia\Pqr\models\PqrForm;
use Saia\core\DatabaseConnection;
use Saia\Pqr\models\PqrNotyMessage;
use Saia\Pqr\controllers\services\PqrFormService;

class PqrNotyMessageController extends Controller
{

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
            $Connection = DatabaseConnection::getDefaultConnection();
            $Connection->beginTransaction();

            $PqrNotyMessage = new PqrNotyMessage($this->request['id']);
            $PqrNotyMessage->setAttributes($this->request['data']);
            if ($PqrNotyMessage->update()) {
                $PqrFormService = new PqrFormService(PqrForm::getPqrFormActive());
                $Response->data = $PqrFormService->getDataPqrNotyMessages();
                $Response->success = 1;
            }

            $Connection->commit();
        } catch (\Exception $th) {
            $Connection->rollBack();
            $Response->message = $th->getMessage();
        }
        return $Response;
    }
}
