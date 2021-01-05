<?php

namespace App\Bundles\pqr\Services\controllers\AddEditFormat\fields;

class AutocompleteD extends Field implements IField
{
    use TField;

    /**
     * @inheritDoc
     */
    public function getValues(): array
    {
        $data = array_merge($this->getDefaultValues(), [
            'tipo_dato' => 'integer',
            'longitud' => 11,
            'valor' => '{*autocompleteD*}',
            'etiqueta_html' => 'Method'
        ]);

        if (!$this->PqrFormField->active) {
            $data['etiqueta_html'] = 'Hidden';
            $data['opciones'] = '{"type":"hidden"}';
        }

        return $data;
    }
}
