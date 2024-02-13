<?php

namespace App\Bundles\pqr\Services\controllers\customFields;

use App\Bundles\pqr\Services\models\PqrFormField;
use Saia\controllers\generator\webservice\IWsFields;

class Tratamiento implements IWsFields
{

    /**
     * Instancia de PqrFormField
     *
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     * @var PqrFormField
     */
    protected PqrFormField $PqrFormField;

    public function __construct(PqrFormField $PqrFormField)
    {
        $this->PqrFormField = $PqrFormField;
    }

    public function getLibrariesWs(): array
    {
        return [];
    }

    public function getAdditionHTMLWs(): string
    {
        $setting = $this->PqrFormField->getSetting();

        $infoUrl = '';
        if ($setting->url) {
            $infoUrl = '<p class="text-center">
            <a href="' . $setting->url . '" target="_blank">Condiciones de uso y políticas de privacidad</a>
          </p>';
        }

        return <<<HTML
        <div class="form-group" id="group_{$this->PqrFormField->name}">
            <h5 class="text-center">AUTORIZACIÓN PARA EL TRATAMIENTO DE INFORMACIÓN</h5>
            <p class="text-justify">$setting->tratamiento</p>
            $infoUrl
            <div class="checkbox check-danger input-group">
                <input type="checkbox" name="{$this->PqrFormField->name}" id="{$this->PqrFormField->name}" value="1" aria-required="true" class="required">
                <label for="{$this->PqrFormField->name}" class="mr-3">
                    ACEPTO LOS TÉRMINOS Y CONDICIONES
                </label>
            </div>
            <label id="{$this->PqrFormField->name}-error" class="error" for="{$this->PqrFormField->name}" style="display: none;"></label>
        </div>
HTML;
    }

    /**
     * @inheritDoc
     */
    public function getAdditionJsWs(): string
    {
        return '';
    }

    /**
     * @inheritDoc
     */
    public function getEditionHTMLWs(): string
    {
        return '';
    }

    /**
     * @inheritDoc
     */
    public function getEditionJsWs(): string
    {
        return '';
    }
}
