<?php

namespace Saia\Pqr\Controllers;

use Saia\Pqr\Models\PqrForm;

class PqrFormController
{
    public $request;

    public function __construct(array $request = null)
    {
        $this->request = $request;
    }

    public function index()
    {
        $Response = (object) [
            'success' => 1,
            'data' => []
        ];

        if ($PqrForm = PqrForm::findAllByAttributes()) {
            $Response->data = $PqrForm[0]->getAttributes();
        };

        return $Response;
    }

    public function publish()
    {
        $Response = (object) [
            'success' => 1,
            'data' => []
        ];

        return $Response;
    }
}
