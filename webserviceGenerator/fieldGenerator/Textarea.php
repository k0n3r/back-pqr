<?php

namespace Saia\Pqr\webserviceGenerator\fieldGenerator;

use Saia\models\formatos\CamposFormato;
use Saia\Pqr\webserviceGenerator\IWsFields;

class Textarea extends Field implements IWsFields
{
    public function aditionalFiles(): array
    {
        return [];
    }

    public function htmlContent(): string
    {
        $requiredClass = $this->getRequiredClass();
        $title = $this->CamposFormato->ayuda ? " title='{$this->CamposFormato->ayuda}'" : '';
        $placeholder = $this->CamposFormato->placeholder ? "placeholder='{$this->CamposFormato->placeholder}'" : '';

        $code = <<<PHP
        <div class="form-group form-group-default {$requiredClass}" id="group_{$this->CamposFormato->nombre}">
            <label{$title}">
                {$this->getLabel()}
            </label>
            <textarea name="{$this->CamposFormato->nombre}" {$placeholder}
                id="{$this->CamposFormato->nombre}" 
                rows="3" 
                class="form-control {$requiredClass}"
            >{$this->getDefaultValue()}</textarea>
        </div>
PHP;

        return $code;
    }

    public function jsContent()
    {
        return;
    }
}
