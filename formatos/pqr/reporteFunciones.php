<?php
$max_salida = 10;
$rootPath = $ruta = '';

while ($max_salida > 0) {
    if (is_file($ruta . 'sw.js')) {
        $rootPath = $ruta;
        break;
    }

    $ruta .= '../';
    $max_salida--;
}

include_once $rootPath . 'app/vendor/autoload.php';

use Saia\controllers\DateController;
use Saia\Pqr\formatos\pqr\FtPqr;

function view(int $iddocumento, $numero): String
{
    return $numero;
}

function dateRadication($date): string
{
    return DateController::convertDate($date);
}

function options(int $iddocumento)
{
    $code = <<<HTML
    <div class="dropdown">
        <button class="btn bg-institutional mx-1" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <i class="fa fa-ellipsis-v"></i>
        </button>
        <div class="dropdown-menu dropdown-menu-left bg-white" role="menu" style="">
            <a href="#" class="dropdown-item addResponsable" data-id="{$iddocumento}">
                <i class="fa fa-user"></i> Asignar Responsable
            </a>
            <a href="#" class="dropdown-item cancel" data-id="{$iddocumento}">
                <i class="fa fa-exclamation-triangle"></i> Anular
            </a>
        </div>
    </div>
HTML;

    return $code;
}
