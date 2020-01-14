<?php

namespace Saia\Pqr\Controllers;

use Exception;
use Saia\core\DatabaseConnection;
use Saia\Pqr\Models\PqrFormField;

class PqrFormFieldController
{
    public $request;

    public function __construct(array $request = null)
    {
        $this->request = $request;
    }

    public function index(): object
    {
        $Response = (object) [
            'success' => 1,
            'data' => []
        ];

        $Instances = PqrFormField::findAllByAttributes([
            'active' => 1
        ]);

        $data = [];
        foreach ($Instances as $Instance) {
            $data[] = $Instance->getDataAttributes();
        }
        $Response->data = $data;

        return $Response;
    }

    public function store(): object
    {
        $Response = (object) [
            'success' => 1,
            'data' => []
        ];
        $params = $this->request['params'];

        $cadena = trim(preg_replace('/[^a-z]/', '_', strtolower($params['label'])));
        $cadena = implode('_', array_filter(explode('_', $cadena)));
        $name = trim(substr($cadena, 0, 20), '_');

        if (PqrFormField::findAllByAttributes([
            'name' => $name
        ])) {
            $caracterFinal = substr($name, -1);
            $consecutivo = is_numeric($caracterFinal) ? (int) $caracterFinal++ : 1;
            $name = $name . '_' . $consecutivo;
        }

        $defaultFields = [
            'name' => $name,
            'active' => 1,
            'setting' => json_encode($params['setting'])
        ];

        try {
            $conn = DatabaseConnection::beginTransaction();

            $attributes = array_merge($params, $defaultFields);

            $PqrFormField = new PqrFormField();
            $PqrFormField->setAttributes($attributes);

            if ($PqrFormField->save()) {
                $conn->commit();
                $Response->data = $PqrFormField->getDataAttributes();
            } else {
                throw new Exception("No fue posible guardar", 1);
            }
        } catch (Exception $th) {
            $conn->rollBack();
            $Response->success = 0;
            $Response->message = $th->getMessage();
        }

        return $Response;
    }

    public function destroy(): object
    {
        $Response = (object) [
            'success' => 0
        ];

        try {
            $conn = DatabaseConnection::beginTransaction();

            $PqrFormField = new PqrFormField($this->request['id']);
            if ($PqrFormField->delete()) {
                $conn->commit();
                $Response->success = 1;
            } else {
                throw new Exception("No fue posible eliminar", 1);
            }
        } catch (Exception $th) {
            $conn->rollBack();
            $Response->success = 0;
            $Response->message = $th->getMessage();
        }

        return $Response;
    }

    public function update()
    {
        $Response = (object) [
            'success' => 0
        ];
        $params = $this->request['params']['dataField'];
        $id = $this->request['params']['id'];

        $params['setting'] = json_encode($params['setting']);

        try {
            $conn = DatabaseConnection::beginTransaction();

            $PqrFormField = new PqrFormField($id);
            $PqrFormField->setAttributes($params);

            if ($PqrFormField->update()) {
                $conn->commit();
                $Response->success = 1;
                $Response->data = $PqrFormField->getDataAttributes();
            } else {
                throw new Exception("No fue posible eliminar", 1);
            }
        } catch (Exception $th) {
            $conn->rollBack();
            $Response->success = 0;
            $Response->message = $th->getMessage();
        }

        return $Response;
    }
}
