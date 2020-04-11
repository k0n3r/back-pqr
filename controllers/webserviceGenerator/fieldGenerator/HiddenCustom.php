<?php

namespace Saia\Pqr\Controllers\WebserviceGenerator\FieldGenerator;

class HiddenCustom implements FieldFormatGeneratorInterface
{

    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function getAditionalFiles(): array
    {
        return [];
    }

    public function getJsAditionalContent(): string
    {
        return '';
    }

    public function getFieldContent(): string
    {
        $value = $this->data['value'] ?? '';
        $code = <<<PHP
        <input type="hidden" id="{$this->data['nombre']}" name="{$this->data['nombre']}" value="{$value}" maxLength="250"/>
PHP;
        return $code;
    }
}
