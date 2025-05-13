<?php

namespace App\Bundles\pqr\Services\controllers\AddEditFormat\fields;

class Date extends Field implements IField
{
    use TField;

    /**
     * @inheritDoc
     */
    public function getValues(): array
    {
        $PqrFormField = $this->getPqrFormField();
        $type = $PqrFormField->getSetting()->dateType;

        $data = array_merge($this->getDefaultValues(), [
            'tipo_dato'   => 'datetime',
            'placeholder' => $PqrFormField->getSetting()->placeholder,
            'opciones'    => '{"hoy":true,"tipo":"'.$type.'"}',
        ]);

        if (!$PqrFormField->active) {
            $data['etiqueta_html'] = 'Hidden';
            $data['opciones'] = '{"type":"hidden"}';
        }

        return $data;
    }
}
