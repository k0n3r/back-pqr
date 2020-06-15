<?php

namespace Saia\Pqr\controllers\addEditFormat\fields;

class Select extends Field implements IField
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
        ]);

        if (!$this->PqrFormField->active) {
            $data['etiqueta_html'] = 'Hidden';
            $data['opciones'] = '{"type":"hidden"}';
        }

        return $data;
    }
}
