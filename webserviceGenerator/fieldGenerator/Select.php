<?php

namespace Saia\Pqr\webserviceGenerator\fieldGenerator;

use Saia\models\formatos\CamposFormato;
use Saia\Pqr\webserviceGenerator\IWsFields;

class Select extends Field implements IWsFields
{
    public function aditionalFiles(): array
    {
        return [
            'views/assets/node_modules/select2/dist/js/select2.min.js',
            'views/assets/node_modules/select2/dist/js/i18n/es.js',
            'views/assets/node_modules/select2/dist/css/select2.min.css'
        ];
    }

    public function htmlContent(): string
    {
        $requiredClass = $this->getRequiredClass();
        $title = $this->CamposFormato->ayuda ? " title='{$this->CamposFormato->ayuda}'" : '';

        $code = "<div class='form-group form-group-default form-group-default-select2 {$requiredClass}' id='group_{$this->CamposFormato->nombre}'>
            <label{$title}>{$this->getLabel()}</label>
            <select class='full-width {$requiredClass}' name='{$this->CamposFormato->nombre}' id='{$this->CamposFormato->nombre}'>
            <option value=''>Por favor seleccione...</option>";

        foreach ($this->CamposFormato->getActiveRadioOptions() as $CampoOpciones) {
            $code .= "<option value='{$CampoOpciones->getPK()}'>
                {$CampoOpciones->valor}
            </option>";
        }

        $code .= "</select>
        </div>";

        return $code;
    }

    public function jsContent(): string
    {
        return "$('#{$this->CamposFormato->nombre}').select2();";
    }
}
