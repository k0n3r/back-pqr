<?php

namespace App\Bundles\pqr\Services\controllers\AddEditFormat\fields;

class AutocompleteM extends Field implements IField
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
            'valor' => '{*autocompleteM*}',
            'etiqueta_html' => 'Method'
        ]);

        if (!$this->getPqrFormField()->active) {
            $data['etiqueta_html'] = 'Hidden';
            $data['opciones'] = '{"type":"hidden"}';
        }

        return $data;
    }
}
