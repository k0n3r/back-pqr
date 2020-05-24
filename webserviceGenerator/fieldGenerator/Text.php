<?php

namespace Saia\Pqr\webserviceGenerator\fieldGenerator;

use Saia\models\formatos\CamposFormato;
use Saia\Pqr\webserviceGenerator\IWsFields;

class Text extends Field implements IWsFields
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
        <div class="form-group form-group-default {$requiredClass}"  id="group_{$this->CamposFormato->nombre}">
            <label{$title}>{$this->getLabel()}</label>
            <input class="form-control {$requiredClass}" type="{$this->getType()}" id="{$this->CamposFormato->nombre}" name="{$this->CamposFormato->nombre}" value="{$this->getDefaultValue()}" {$placeholder} maxLength="250"/>
        </div>
PHP;
        return $code;
    }

    protected function getType(): string
    {
        $options = json_decode($this->CamposFormato->opciones);

        return $options->type ?? 'text';
    }

    public function jsContent()
    {
        return;
    }
}
