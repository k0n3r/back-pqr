<?php

namespace Saia\Pqr\Controllers\WebserviceGenerator\FieldGenerator;


interface FieldGenerator
{
    public function getFieldContent(): string;
}
