<?php

namespace Saia\Pqr\controllers;

use Exception;
use Saia\core\DatabaseConnection;
use Saia\Pqr\models\PqrResponseTemplate;

class PqrResponseTemplateController extends Controller
{

    /**
     * Obtiene las plantillas
     *
     * @return object
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function index(): object
    {
        $data = [];

        $records = PqrResponseTemplate::findAllByAttributes([]);
        foreach ($records as $PqrResponseTemplate) {
            $data[] = $PqrResponseTemplate->getDataAttributes();
        }

        return (object) [
            'success' => 1,
            'data' => $data
        ];
    }

    /**
     * Almacena un nueva plantilla
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

        try {
            $conn = DatabaseConnection::getDefaultConnection();
            $conn->beginTransaction();

            if (PqrResponseTemplate::findByAttributes([
                'name' => $this->request['name']
            ])) {
                throw new Exception("El nombre de la plantilla ya existe", 200);
            }

            $Template = new PqrResponseTemplate();
            $Template->setAttributes($this->request);

            if (!$Template->save()) {
                throw new Exception("No fue posible crear la plantilla", 200);
            }
            $conn->commit();
            $Response->data = $Template->getDataAttributes();
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

        $params = $this->request['dataField'];
        $id = $this->request['id'];

        try {
            $conn = DatabaseConnection::getDefaultConnection();
            $conn->beginTransaction();

            $Template = new PqrResponseTemplate($id);
            $Template->setAttributes($params);

            if (!$Template->update()) {
                throw new Exception("No fue posible actualizar", 1);
            }

            $Response->success = 1;
            $Response->data = $Template->getDataAttributes();
            $conn->commit();
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
            $conn = DatabaseConnection::getDefaultConnection();
            $conn->beginTransaction();

            $Template = new PqrResponseTemplate($this->request['id']);

            if (!$Template->delete()) {
                throw new Exception("No fue posible eliminar", 1);
            }

            $Response->success = 1;
            $conn->commit();
        } catch (Exception $th) {
            $conn->rollBack();
            $Response->message = $th->getMessage();
        }

        return $Response;
    }
}
