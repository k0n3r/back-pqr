<?php

namespace Saia\Pqr\controllers\addEditFormat\fields;

class Checkbox extends Field implements IField
{

    use TField;

    /**
     * @inheritDoc
     */
    public function getValues(): array
    {
        return $this->getDefaultValues();
    }
}
