<?php

namespace Saia\Pqr\controllers\AddEditFormat\fields;

class Hidden extends Field implements IField
{
    use TField;

    /**
     * @inheritDoc
     */
    public function getValues(): array
    {
        return array_merge($this->getDefaultValues(), [
            'opciones' => '{"type":"hidden"}'
        ]);
    }
}
