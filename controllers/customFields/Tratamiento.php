<?php

namespace Saia\Pqr\controllers\customFields;

use Saia\Pqr\models\PqrFormField;
use Saia\controllers\generator\webservice\IWsFields;

class Tratamiento implements IWsFields
{

    /**
     * Instancia de PqrFormField
     *
     * @var PqrFormField
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    protected PqrFormField $PqrFormField;


    public function __construct(PqrFormField $PqrFormField)
    {
        $this->PqrFormField = $PqrFormField;
    }

    public function aditionalFiles(): array
    {
        return [];
    }

    public function htmlContent(): string
    {
        $setting = $this->PqrFormField->getSetting();

        $infoUrl = '';
        if ($setting->url) {
            $infoUrl = '<p class="text-center">
            <a href="' . $setting->url . '" target="_blank">Condiciones de uso y políticas de privacidad</a>
          </p>';
        }

        $code = <<<HTML
         <div class="form-group" id="group_{$this->PqrFormField->name}">
        <h5 class="text-center">AUTORIZACIÓN PARA EL TRATAMIENTO DE INFORMACIÓN</h5>
        <p class="text-justify">{$setting->tratamiento}</p>
        {$infoUrl}
        <p class="text-right">
          <input type="checkbox" name="{$this->PqrFormField->name}" value="1" class="required" /> ACEPTO LOS TERMINOS Y CONDICIONES
        </p>
      </div>
HTML;
        return $code;
    }

    public function jsContent(): ?string
    {
        return NULL;
    }
}
