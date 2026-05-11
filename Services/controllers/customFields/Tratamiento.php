<?php

namespace App\Bundles\pqr\Services\controllers\customFields;

use App\Bundles\pqr\Entity\PqrFormField as PqrFormFieldEntity;
use Saia\controllers\generator\webservice\IWsFields;

class Tratamiento implements IWsFields
{
    protected PqrFormFieldEntity $PqrFormField;

    public function __construct(PqrFormFieldEntity $PqrFormField)
    {
        $this->PqrFormField = $PqrFormField;
    }

    public function getLibrariesWs(): array
    {
        return [];
    }

    public function getAdditionHTMLWs(): string
    {
        $setting = $this->PqrFormField->getSettingDecoded();
        $name = $this->PqrFormField->getName();

        $infoUrl = '';
        if ($setting->url ?? null) {
            $infoUrl = '<p class="text-center">
            <a href="'.$setting->url.'" target="_blank">Condiciones de uso y políticas de privacidad</a>
          </p>';
        }

        return <<<HTML
            <div class="form-group" id="group_{$name}">
                <h5 class="text-center">AUTORIZACIÓN PARA EL TRATAMIENTO DE INFORMACIÓN</h5>
                <p class="text-justify">$setting->tratamiento</p>
                $infoUrl
                <div class="checkbox check-danger input-group">
                    <input type="checkbox" name="{$name}" id="{$name}" value="1" aria-required="true" class="required">
                    <label for="{$name}" class="me-3">
                        ACEPTO LOS TÉRMINOS Y CONDICIONES
                    </label>
                </div>
                <label id="{$name}-error" class="error" for="{$name}" style="display: none;"></label>
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
