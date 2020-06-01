<?php

namespace Saia\Pqr\controllers\addEditFormat\fields;

class Radio extends Field implements IField
{

    use TField;

    /**
     * @inheritDoc
     */
    public function getValues(): array
    {

        return array_merge($this->getDefaultValues(), [
            'tipo_dato' => 'integer',
            'longitud' => 11,
        ]);
    }
}
