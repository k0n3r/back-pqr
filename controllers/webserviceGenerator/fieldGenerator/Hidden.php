<?php

namespace Saia\Pqr\Controllers\WebserviceGenerator\FieldGenerator;

use Saia\models\formatos\CamposFormato;

class Hidden implements FieldGenerator
{
    public function __construct(CamposFormato $CamposFormato)
    {
        parent::__construct($CamposFormato);
    }
}
