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
        $setting = $PqrFormField->getSettingDecoded();

        $data = array_merge($this->getDefaultValues(), [
            'tipo_dato'   => 'datetime',
            'placeholder' => $setting->placeholder,
            'opciones'    => '{"hoy":true,"tipo":"'.$setting->dateType.'"}',
        ]);

        if (!$PqrFormField->isActive()) {
            $data['etiqueta_html'] = 'Hidden';
            $data['opciones'] = '{"type":"hidden"}';
        }

        return $data;
    }
}
