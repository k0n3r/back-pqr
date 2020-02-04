<?php

namespace Saia\Pqr\Controllers\WebserviceGenerator\FieldGenerator;

use Saia\models\formatos\CamposFormato;

class Text implements FieldGenerator
{
    public function __construct(CamposFormato $CamposFormato)
    {
        parent::__construct($CamposFormato);
    }
}
