<?php

namespace Saia\Pqr\Controllers;

use Saia\Pqr\Models\PqrHtmlField;

class PqrHtmlFieldController
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

        $data = PqrHtmlField::findAllByAttributes([
            'active' => 1
        ]);
        $Response->data = $data;

        return $Response;
    }
}
