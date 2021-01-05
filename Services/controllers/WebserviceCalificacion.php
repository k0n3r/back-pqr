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
            'baseUrl' => ABSOLUTE_SAIA_ROUTE,
            'formatId' => $this->Formato->getPK(),
            'content' => $this->jsContent
        ];

        return $this->getContent(
            'app/modules/back_pqr/controllers/templates/formCalificacion.js.php',
            $values
        );
    }
}
