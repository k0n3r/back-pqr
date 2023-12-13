<?php

namespace App\Bundles\pqr\Services\controllers;

use Saia\controllers\generator\webservice\WsFormulario;

class WebserviceCalificacion extends WsFormulario
{
    /**
     * @inheritDoc
     */
    public function getJsContentForm(bool $isEdit = false): string
    {
        return static::getContent(
            'src/Bundles/pqr/Services/controllers/templates/formCalificacion.js.php',
            $this->getDefaultValuesForJsContent($isEdit)
        );
    }
}
