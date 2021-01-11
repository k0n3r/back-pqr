<?php

use Saia\controllers\DateController;

function view(int $iddocumento, $numero): String
{
    $enlace = <<<HTML
    <div class='kenlace_saia'
    enlace='views/documento/index_acordeon.php?documentId={$iddocumento}' 
    conector='iframe'
    titulo='No Registro {$numero}'>
        <button class='btn btn-complete' style='margin:auto'>{$numero}</button>
    </div>
HTML;
    return $enlace;
}

function dateRadication($date, string $format = null): string
{
    if (!$format) {
        $format = DateController::PUBLIC_DATETIME_FORMAT;
    }
    return DateController::convertDate($date, $format);
}
