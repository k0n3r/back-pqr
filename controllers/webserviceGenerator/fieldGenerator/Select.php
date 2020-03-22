<?php

namespace Saia\Pqr\Controllers\WebserviceGenerator\FieldGenerator;

use Saia\models\formatos\CamposFormato;
use Saia\Pqr\Controllers\WebserviceGenerator\WebserviceGenerator;

class Select extends FieldGenerator implements FieldFormatGeneratorInterface
{
    public function __construct(CamposFormato $CamposFormato)
    {
        parent::__construct($CamposFormato);
    }

    public function getAditionalFiles(): array
    {
        return [
            [
                'origin' => 'views/assets/node_modules/select2/dist/js/select2.min.js',
                'fieldName' => 'select2.min.js',
                'type' => WebserviceGenerator::TYPE_JS
            ],
            [
                'origin' => 'views/assets/node_modules/select2/dist/js/i18n/es.js',
                'fieldName' => 'es.js',
                'type' => WebserviceGenerator::TYPE_JS
            ],
            [
                'origin' => 'views/assets/node_modules/select2/dist/css/select2.min.css',
                'fieldName' => 'selec2.min.css',
                'type' => WebserviceGenerator::TYPE_CSS
            ]
        ];
    }

    public function getJsAditionalContent(): string
    {
        return "$('#{$this->CamposFormato->nombre}').select2();";
    }

    public function getFieldContent(): string
    {
        $requiredClass = $this->getRequiredClass();
        $title = $this->CamposFormato->ayuda ? " title='{$this->CamposFormato->ayuda}'" : '';

        $code = "{n}<div class='form-group form-group-default form-group-default-select2 {$requiredClass}' id='group_{$this->CamposFormato->nombre}'>
            <label{$title}>{$this->getLabel()}</label>
            <select class='full-width {$requiredClass}' name='{$this->CamposFormato->nombre}' id='{$this->CamposFormato->nombre}'>
            <option value=''>Por favor seleccione...</option>";

        foreach ($this->CamposFormato->getRadioOptions() as $CampoOpciones) {
            $code .= "<option value='{$CampoOpciones->getPK()}'>
                {$CampoOpciones->valor}
            </option>";
        }

        $code .= "</select>
        </div>";

        return $this->addTab($code);
    }
}
