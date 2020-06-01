<?php

namespace Saia\Pqr\controllers\addEditFormat\fields;

class AutocompleteM extends Field implements IField
{
    use TField;

    /**
     * @inheritDoc
     */
    public function getValues(): array
    {
        throw new \Exception("HACER FIELD METHOD PARA MUNICIPIO", 1);

        return array_merge($this->getDefaultValues(), [
            'opciones' => '{"type":"hidden"}'
        ]);
    }
}
