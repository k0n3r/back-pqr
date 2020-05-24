<?php

namespace Saia\Pqr\webserviceGenerator\fieldGenerator;

use Saia\models\formatos\CamposFormato;

class Email extends Text
{
    protected function getType(): string
    {
        return 'email';
    }
}
