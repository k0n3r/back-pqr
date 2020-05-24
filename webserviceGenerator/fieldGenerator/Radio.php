<?php

namespace Saia\Pqr\webserviceGenerator\fieldGenerator;

use Saia\models\formatos\CamposFormato;
use Saia\Pqr\webserviceGenerator\IWsFields;

class Radio extends Field implements IWsFields
{
    public function aditionalFiles(): array
    {
        return [];
    }

    public function htmlContent(): string
    {
        $options = $this->CamposFormato->getActiveRadioOptions();
        if (!$options) {
            throw new \Exception("Debe indicar las opciones de {$this->CamposFormato->etiqueta}", 200);
        }

        $requiredClass = $this->getRequiredClass();
        $labelRequired = $requiredClass ?
            "<label id='{$this->CamposFormato->nombre}-error' class='error' for='{$this->CamposFormato->nombre}' style='display: none;'></label>"
            : '';

        $title = $this->CamposFormato->ayuda ? " title='{$this->CamposFormato->ayuda}'" : '';


        $code = "<div class='form-group form-group-default {$requiredClass}' id='group_{$this->CamposFormato->nombre}'>
            <label{$title}>{$this->getLabel()}</label>
            <div class='radio radio-success input-group'>";

        foreach ($options as $key => $CampoOpciones) {
            $code .= "<input {$requiredClass} type='radio' name='{$this->CamposFormato->nombre}' id='{$this->CamposFormato->nombre}{$key}' value='{$CampoOpciones->getPK()}' aria-required='true'>
            <label for='{$this->CamposFormato->nombre}{$key}' class='mr-3'>
                {$CampoOpciones->valor}
            </label>";
        }

        $code .= "</div>
            {$labelRequired}
        </div>";

        return $code;
    }

    public function jsContent()
    {
        return;
    }
}
