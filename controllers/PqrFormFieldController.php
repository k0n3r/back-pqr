<?php

namespace Saia\Pqr\Controllers;

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

        $defaultFields = [
            'name' => $name,
            'active' => 1,
            'setting' => json_encode($params['setting'])
        ];

        try {
            $conn = \Connection::beginTransaction();

            $attributes = array_merge($params, $defaultFields);

            $PqrFormField = new PqrFormField();
            $PqrFormField->setAttributes($attributes);

            if ($PqrFormField->save()) {
                $conn->commit();
                $Response->data = $PqrFormField->getDataAttributes();
            } else {
                throw new \Exception("No fue posible guardar", 1);
            }
        } catch (\Exception $th) {
            $conn->rollBack();
            $Response->success = 0;
            $Response->message = $th->getMessage();
        }

        return $Response;
    }
}
