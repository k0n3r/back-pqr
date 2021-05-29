<?php

namespace App\Bundles\pqr\Services\controllers\AddEditFormat\fields;

class Textarea extends Field implements IField
{
    use TField;

    /**
     * @inheritDoc
     */
    public function getValues(): array
    {
        $PqrFormField = $this->getPqrFormField();

        $data = array_merge($this->getDefaultValues(), [
            'tipo_dato' => 'text',
            'longitud' => null,
            'placeholder' => $PqrFormField->getSetting()->placeholder
        ]);

        if (!$PqrFormField->active) {
            $data['etiqueta_html'] = 'Hidden';
            $data['opciones'] = '{"type":"hidden"}';
        }

        return $data;
    }
}
