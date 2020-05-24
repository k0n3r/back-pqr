<?php

namespace Saia\Pqr\webserviceGenerator\fieldGenerator;

use Saia\models\formatos\CamposFormato;
use Saia\Pqr\webserviceGenerator\IWsFields;

class Hidden extends Field implements IWsFields
{
    public function aditionalFiles(): array
    {
        return [];
    }

    public function htmlContent(): string
    {
        $code = <<<PHP
            <input class="form-control" type="hidden" id="{$this->CamposFormato->nombre}" name="{$this->CamposFormato->nombre}" value="{$this->getDefaultValue()}" maxLength="250"/>
PHP;
        return $code;
    }

    public function jsContent()
    {
        return;
    }
}
