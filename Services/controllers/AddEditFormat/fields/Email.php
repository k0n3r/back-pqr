<?php

namespace App\Bundles\pqr\Services\controllers\AddEditFormat\fields;

class Email extends Field implements IField
{
    use TField;

    /**
     * @inheritDoc
     */
    public function getValues(): array
    {
        $PqrFormField = $this->getPqrFormField();

        $data = array_merge($this->getDefaultValues(), [
            'placeholder' => $PqrFormField->getSettingDecoded()->placeholder,
            'opciones'    => json_encode([
                'confirmar_correo' => $PqrFormField->getSettingDecoded()->confirmEmail ?? true,
            ]),
        ]);

        if (!$PqrFormField->isActive()) {
            $data['etiqueta_html'] = 'Hidden';
            $data['opciones'] = '{"type":"hidden"}';
        }

        return $data;
    }
}
