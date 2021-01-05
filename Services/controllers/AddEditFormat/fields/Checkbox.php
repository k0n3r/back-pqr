<?php

namespace App\Bundles\pqr\Services\controllers\AddEditFormat\fields;

class Checkbox extends Field implements IField
{

    use TField;

    /**
     * @inheritDoc
     */
    public function getValues(): array
    {
        $data = $this->getDefaultValues();

        if (!$this->PqrFormField->active) {
            $data['etiqueta_html'] = 'Hidden';
            $data['opciones'] = '{"type":"hidden"}';
        }

        return $data;
    }
}
