<?php

namespace Saia\Pqr\controllers\addEditFormat\fields;

class Textarea extends Field implements IField
{
    use TField;

    /**
     * @inheritDoc
     */
    public function getValues(): array
    {
        $PqrFormField = $this->PqrFormField;

        $data = array_merge($this->getDefaultValues(), [
            'placeholder' => $PqrFormField->getSetting()->placeholder
        ]);

        if (!$this->PqrFormField->active) {
            $data['etiqueta_html'] = 'Hidden';
            $data['opciones'] = '{"type":"hidden"}';
        }

        return $data;
    }
}
