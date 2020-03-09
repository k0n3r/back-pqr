<?php

namespace Saia\Pqr\Controllers;

use Exception;
use Saia\core\DatabaseConnection;
use Saia\Pqr\Models\PqrAnswer;
use Saia\Pqr\Models\PqrResponseTemplate;

class PqrAnswerController
{
    /**
     * Variable que contiene todo el request que llega de las peticiones
     *
     * @var array|null
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public $request;

    public function __construct(array $request = null)
    {
        $this->request = $request;
    }

    /**
     * Almacena una vinculacion de respuesta con pqr
     *
     * @return object
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function store(): object
    {
        $Response = (object) [
            'success' => 1,
            'data' => []
        ];
        var_dump($this->request);

        try {
            $conn = DatabaseConnection::beginTransaction();

            $PqrAnswer = new PqrAnswer();
            $PqrAnswer->setAttributes($this->request);
            if ($PqrAnswer->save()) {
                $conn->commit();
            } else {
                throw new Exception("No fue posible guardar la respuesta", 1);
            }
        } catch (Exception $th) {
            $conn->rollBack();
            $Response->success = 0;
            $Response->message = $th->getMessage();
        }

        return $Response;
    }


    /**
     * Actualiza una plantilla
     *
     * @return object
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function update(): object
    {
        $Response = (object) [
            'success' => 0
        ];

        $params = $this->request['params']['dataField'];
        $id = $this->request['params']['id'];


        try {
            $conn = DatabaseConnection::beginTransaction();

            $Template = new PqrResponseTemplate($id);
            $Template->setAttributes($params);

            if ($Template->update()) {
                $conn->commit();
                $Response->success = 1;
                $Response->data = $Template->getAttributes();
            } else {
                throw new Exception("No fue posible actualizar", 1);
            }
        } catch (Exception $th) {
            $conn->rollBack();
            $Response->message = $th->getMessage();
        }

        return $Response;
    }

    /**
     * Elimina una plantilla
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
            $conn = DatabaseConnection::beginTransaction();

            $Template = new PqrResponseTemplate($this->request['id']);
            if ($Template->delete()) {
                $conn->commit();
                $Response->success = 1;
            } else {
                throw new Exception("No fue posible eliminar", 1);
            }
        } catch (Exception $th) {
            $conn->rollBack();
            $Response->message = $th->getMessage();
        }

        return $Response;
    }
}
