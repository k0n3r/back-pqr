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

        $confirmEmail = $PqrFormField->getSettingDecoded()->confirmEmail ?? true;

        $data = array_merge($this->getDefaultValues(), [
            'placeholder' => $PqrFormField->getSettingDecoded()->placeholder,
            'opciones'    => json_encode([
                // El valor viaja por un PUT/POST form-urlencoded, que convierte
                // cualquier booleano a texto literal ("true"/"false", ambas
                // truthy); filter_var normaliza eso y de paso sana registros
                // ya guardados así por este mismo motivo.
                'confirmar_correo' => filter_var($confirmEmail, FILTER_VALIDATE_BOOLEAN),
            ]),
        ]);

        if (!$PqrFormField->isActive()) {
            $data['etiqueta_html'] = 'Hidden';
            $data['opciones'] = '{"type":"hidden"}';
        }

        return $data;
    }
}
