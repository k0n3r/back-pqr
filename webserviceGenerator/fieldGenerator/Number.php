<?php

namespace Saia\Pqr\webserviceGenerator\fieldGenerator;

use Saia\models\formatos\CamposFormato;

class Number extends Text
{
    protected function getType(): string
    {
        return 'number';
    }
}
