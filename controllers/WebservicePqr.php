<?php

namespace Saia\Pqr\Controllers;

use Saia\Pqr\Models\PqrForm;
use Saia\Pqr\Controllers\WebserviceGenerator\WebserviceGenerator;
use Saia\Pqr\Controllers\WebserviceGenerator\GenerateFieldContent;

class WebservicePqr extends WebserviceGenerator
{
    public $PqrForm;

    public function __construct(PqrForm $PqrForm)
    {
        $this->PqrForm = $PqrForm;
    }

    protected function getContent(): string
    {
        $code = '';
        foreach ($this->PqrForm->getPqrFormFieldsActive() as $PqrFormField) {
            $class = $this->resolveClass($PqrFormField->PqrHtmlField->type);
            $GenerateFieldContent = new GenerateFieldContent(new $class());
            $code .= $GenerateFieldContent->getContent();
        }

        return $code;
    }
}
