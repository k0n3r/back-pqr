<?php

namespace Saia\Pqr\webserviceGenerator\fieldGenerator;

use Saia\models\formatos\CamposFormato;
use Saia\Pqr\webserviceGenerator\IWsFields;

class Label extends Field implements IWsFields
{
    public function aditionalFiles(): array
    {
        return [];
    }

    public function htmlContent(): string
    {

        $label = mb_strtoupper($this->getLabel());
        $title = $this->CamposFormato->ayuda ? " title='{$this->CamposFormato->ayuda}'" : '';

        $code = "<div id='group_{$this->CamposFormato->nombre}'>
            <h5>
                <label{$title}>{$label}</label>
            </h5>
        </div>";

        return $code;
    }

    public function jsContent()
    {
        return;
    }
}
