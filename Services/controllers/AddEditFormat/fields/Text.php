<?php

namespace App\Bundles\pqr\Services\controllers\AddEditFormat\fields;

class Text extends Field implements IField
{
    use TField;

    /**
     * @inheritDoc
     */
    public function getValues(): array
    {
        $PqrFormField = $this->getPqrFormField();
        $typeHtml = $PqrFormField->getHtmlField()->getType();

        $data = array_merge($this->getDefaultValues(), [
            'placeholder' => $PqrFormField->getSettingDecoded()->placeholder,
            'opciones'    => '{"type":"'.$typeHtml.'"}',
        ]);

        if (!$PqrFormField->isActive()) {
            $data['etiqueta_html'] = 'Hidden';
            $data['opciones'] = '{"type":"hidden"}';
        }

        return $data;
    }
}
