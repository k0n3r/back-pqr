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
            'recaptchaPublicKey' => $_SERVER['APP_RECAPTCHA_PUBLIC_KEY'],
            'baseUrl' => $_SERVER['APP_DOMAIN'],
            'formatId' => $this->Formato->getPK(),
            'content' => $this->jsContent,
            'urlSaveFt' => 'api/captcha/saveDocument'
        ];

        return $this->getContent(
            'src/Bundles/pqr/Services/controllers/templates/formCalificacion.js.php',
            $values
        );
    }
}
