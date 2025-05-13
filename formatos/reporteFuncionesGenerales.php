<?php

use Saia\controllers\DateController;

function view(int $iddocumento, $numero): string
{
    return <<<HTML
        <div class='kenlace_saia'
        data-enlace='views/documento/index_acordeon.php?documentId=$iddocumento' 
        title='No Registro $numero'>
            <button class='btn btn-complete' style='margin:auto'>$numero</button>
        </div>
        HTML;
}

function dateRadication($date, string $format = null): string
{
    if (!$format) {
        $format = DateController::PUBLIC_DATETIME_FORMAT;
    }

    return DateController::convertDate($date, $format);
}
