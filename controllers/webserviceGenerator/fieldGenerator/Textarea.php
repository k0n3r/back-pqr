<?php

namespace Saia\Pqr\Controllers\WebserviceGenerator\FieldGenerator;

use Saia\models\formatos\CamposFormato;

class Textarea extends FieldGenerator implements FieldFormatGeneratorInterface
{
    public function __construct(CamposFormato $CamposFormato)
    {
        parent::__construct($CamposFormato);
    }

    public function getAditionalFiles(): array
    {
        return [];
    }

    public function getJsAditionalContent(): string
    {
        return '';
    }

    public function getFieldContent(): string
    {
        $requiredClass = $this->getRequiredClass();
        $title = $this->CamposFormato->ayuda ? " title='{$this->CamposFormato->ayuda}'" : '';
        $placeholder = $this->CamposFormato->placeholder ? "placeholder='{$this->CamposFormato->placeholder}'" : '';

        $code = <<<PHP
        {n}<div class="form-group form-group-default {$requiredClass}" id="group_{$this->CamposFormato->nombre}">
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

        return $this->addTab($code);
    }
}
