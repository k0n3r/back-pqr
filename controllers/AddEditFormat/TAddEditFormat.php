<?php

namespace Saia\Pqr\Controllers\AddEditFormat;

use Saia\controllers\generator\FormatGenerator;

trait TAddEditFormat
{
    protected function FormatGenerator(int $idformato)
    {
        $FormatGenerator = new FormatGenerator($idformato);
        $FormatGenerator->generate();
        $FormatGenerator->createModule();
    }
}
