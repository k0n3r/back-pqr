<?php

namespace Saia\Pqr\webserviceGenerator\fieldGenerator;

use Saia\models\formatos\CamposFormato;
use Saia\Pqr\webserviceGenerator\IWsFields;

class Date extends Field implements IWsFields
{

    public function aditionalFiles(): array
    {
        return [];
    }

    public function htmlContent(): string
    {
        throw new \Exception("PENDIENTE POR DESARROLLAR NO ES NECESRIO EN PQR", 200);

        return '';
    }

    public function jsContent()
    {
        return;
    }
}
