<?php

namespace Saia\Pqr\Controllers;

use Saia\Pqr\Models\PqrForm;
use Saia\Pqr\Controllers\WebserviceGenerator\WebserviceGenerator;

class WebservicePqr extends WebserviceGenerator
{
    public $PqrForm;

    public function __construct(PqrForm $PqrForm)
    {
        $this->PqrForm = $PqrForm;
    }

    protected function getFormatFields(): array
    {
        $data = [];

        foreach ($this->PqrForm->getPqrFormFieldsActive() as $PqrFormField) {
            $data[] = $PqrFormField->CamposFormato;
        }

        return $data;
    }

    protected function getNameForm(): string
    {
        return $this->PqrForm->label;
    }

    protected function getContent(): string
    {
        $code = parent::getContentDefault();

        return $code;
    }

    protected function createJsContent(): string
    {
        return parent::jsContentDefault('app/modules/back_pqr/app/webservice.php');
    }
}
