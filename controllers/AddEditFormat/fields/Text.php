<?php

namespace Saia\Pqr\controllers\addEditFormat\fields;

class Text extends Field implements IField
{
    use TField;

    /**
     * @inheritDoc
     */
    public function getValues(): array
    {
        $PqrFormField = $this->PqrFormField;
        $typeHtml = $PqrFormField->PqrHtmlField->type;

        return array_merge($this->getDefaultValues(), [
            'placeholder' => $PqrFormField->getSetting()->placeholder,
            'opciones' => '{"type":"' . $typeHtml . '"}'
        ]);
    }
}
