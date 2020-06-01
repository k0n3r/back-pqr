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

        return array_merge($this->getDefaultValues(), [
            'placeholder' => $PqrFormField->getSetting()->placeholder
        ]);
    }
}
