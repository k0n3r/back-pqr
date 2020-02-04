<?php

namespace Saia\Pqr\Controllers\WebserviceGenerator;

use Saia\Pqr\Controllers\WebserviceGenerator\FieldGenerator\FieldGenerator;

class GenerateFieldContent
{
    public $TypeField;

    public function __construct(FieldGenerator $TypeField)
    {
        $this->TypeField = $TypeField;
    }

    public function getContent(): string
    {
        return $this->TypeField->getFieldContent();
    }
}
