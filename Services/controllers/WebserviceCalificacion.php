<?php

namespace App\Bundles\pqr\Services\controllers;

use Saia\controllers\generator\webservice\WsFormulario;

class WebserviceCalificacion extends WsFormulario
{
    /**
     * @inheritDoc
     */
    public function getJsContentForm(): string
    {
        $values = [
            'baseUrl' => $_SERVER['APP_DOMAIN'],
            'formatId' => $this->Formato->getPK(),
            'content' => $this->jsContent
        ];

        return $this->getContent(
            'src/Bundles/pqr/Services/controllers/templates/formCalificacion.js.php',
            $values
        );
    }
}
