<?php

namespace Saia\Pqr\Controllers\WebserviceGenerator\FieldGenerator;

use Saia\models\formatos\CamposFormato;

class Radio extends FieldGenerator implements FieldFormatGeneratorInterface
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
        $labelRequired = $requiredClass ?
            "<label id='{$this->CamposFormato->nombre}-error' class='error' for='{$this->CamposFormato->nombre}' style='display: none;'></label>"
            : '';

        $title = $this->CamposFormato->ayuda ? " title='{$this->CamposFormato->ayuda}'" : '';


        $code = "{n}<div class='form-group form-group-default {$requiredClass}' id='group_{$this->CamposFormato->nombre}'>
            <label{$title}>{$this->getLabel()}</label>
            <div class='radio radio-success input-group'>";

        foreach ($this->getMultipleOptions() as $key => $CampoOpciones) {
            $code .= "<input {$requiredClass} type='radio' name='{$this->CamposFormato->nombre}' id='{$this->CamposFormato->nombre}{$key}' value='{$CampoOpciones->getPK()}' aria-required='true'>
            <label for='{$this->CamposFormato->nombre}{$key}' class='mr-3'>
                {$CampoOpciones->valor}
            </label>";
        }

        $code .= "</div>
            {$labelRequired}
        </div>";

        return $this->addTab($code);
    }
}
