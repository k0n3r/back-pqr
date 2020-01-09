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

    public function store()
    {
        $Response = (object) [
            'success' => 1,
            'data' => []
        ];
        $params = $this->request['params'];

        $defaultFields = [
            'name' => strtolower($params['label']),
            'active' => 1,
            'setting' => json_encode($params['setting'])
        ];

        try {
            //$conn = \Connection::beginTransaction();

            $attributes = array_merge($params, $defaultFields);
            print_r($attributes);

            /*$newPqrFormField = PqrFormField::create($attributes);
            $data = new PqrFormFieldResource($newPqrFormField);

            $conn->commit();
            $Response->data = $data;*/
        } catch (\Exception $th) {
            //$conn->rollBack();
            $Response->success = 0;
            $Response->message = $th->getMessage();
        }

        return $Response;
    }
}
