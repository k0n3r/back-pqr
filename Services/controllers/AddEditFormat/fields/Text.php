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
        $PqrFormField = $this->PqrFormField;
        $typeHtml = $PqrFormField->PqrHtmlField->type;

        $data = array_merge($this->getDefaultValues(), [
            'placeholder' => $PqrFormField->getSetting()->placeholder,
            'opciones' => '{"type":"' . $typeHtml . '"}'
        ]);

        if (!$this->PqrFormField->active) {
            $data['etiqueta_html'] = 'Hidden';
            $data['opciones'] = '{"type":"hidden"}';
        }

        return $data;
    }
}
