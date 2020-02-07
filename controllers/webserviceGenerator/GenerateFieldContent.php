<?php

namespace Saia\Pqr\Controllers\WebserviceGenerator;

use Saia\Pqr\Controllers\WebserviceGenerator\FieldGenerator\FieldFormatGeneratorInterface;

class GenerateFieldContent
{
    protected $TypeField;

    public function __construct(FieldFormatGeneratorInterface $TypeField)
    {
        $this->TypeField = $TypeField;
    }

    public function getContent(): string
    {
        return $this->TypeField->getFieldContent();
    }

    public function getAditionalFiles(): array
    {
        return $this->TypeField->getAditionalFiles();
    }

    public function getJsAditionalContent(): string
    {
        return $this->TypeField->getJsAditionalContent();
    }
}
