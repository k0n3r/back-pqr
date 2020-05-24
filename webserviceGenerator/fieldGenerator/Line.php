<?php

namespace Saia\Pqr\webserviceGenerator\fieldGenerator;

use Saia\models\formatos\CamposFormato;
use Saia\Pqr\webserviceGenerator\IWsFields;

class Line extends Field implements IWsFields
{
    public function aditionalFiles(): array
    {
        return [];
    }

    public function htmlContent(): string
    {
        $code = "<div id='group_{$this->CamposFormato->nombre}'>
            <hr class='border'>
        </div>";

        return $code;
    }

    public function jsContent()
    {
        return;
    }
}
