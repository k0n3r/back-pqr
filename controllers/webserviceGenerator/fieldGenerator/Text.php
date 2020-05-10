<?php

namespace Saia\Pqr\controllers\webserviceGenerator\fieldGenerator;

use Saia\models\formatos\CamposFormato;

class Text extends FieldGenerator implements FieldFormatGeneratorInterface
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
        $options = json_decode($this->CamposFormato->opciones);
        $type = $options->type ?? 'text';

        $code = <<<PHP
        {n}<div class="form-group form-group-default {$requiredClass}"  id="group_{$this->CamposFormato->nombre}">
            <label{$title}>{$this->getLabel()}</label>
            <input class="form-control {$requiredClass}" type="{$type}" id="{$this->CamposFormato->nombre}" name="{$this->CamposFormato->nombre}" value="{$this->getDefaultValue()}" {$placeholder} maxLength="250"/>
        </div>
PHP;
        return $this->addTab($code);
    }
}
