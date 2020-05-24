<?php

namespace Saia\Pqr\webserviceGenerator\fieldGenerator;

use Saia\models\formatos\CamposFormato;
use Saia\Pqr\webserviceGenerator\IWsFields;

class Paragraph extends Field implements IWsFields
{
    public function aditionalFiles(): array
    {
        return [];
    }

    public function htmlContent(): string
    {
        if (!$this->CamposFormato->valor) {
            throw new \Exception("Debe indicar el texto del parrafo para el campo {$this->CamposFormato->etiqueta}", 200);
        }

        $code = "<p id='group_{$this->CamposFormato->nombre}'>
                {$this->CamposFormato->valor}
        </p>";

        return $code;
    }


    public function jsContent()
    {
        return;
    }
}
