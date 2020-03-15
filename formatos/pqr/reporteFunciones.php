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
use Saia\models\documento\Documento;
use Saia\Pqr\formatos\pqr\FtPqr;
use Saia\Pqr\Helpers\UtilitiesPqr;

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

function dateRadication($date): string
{
    return DateController::convertDate($date);
}

function totalTask(int $iddocumento): string
{
    $data = UtilitiesPqr::getFinishTotalTask(new Documento($iddocumento));

    return "{$data['finish']}/{$data['total']}";
}

function options(int $iddocumento, string $estado): string
{
    switch ($estado) {
        case FtPqr::ESTADO_PROCESO:
            $options = <<<HTML
            <a href="#" class="dropdown-item addTask" data-id="{$iddocumento}">
               <i class="fa fa-plus"></i> Asignar Tarea
           </a>
           <a href="#" class="dropdown-item viewTask" data-id="{$iddocumento}">
               <i class="fa fa-eye"></i> Tareas
           </a>
HTML;
            break;

        case FtPqr::ESTADO_TERMINADO:
            $options = <<<HTML
            <a href="#" class="dropdown-item answer2" data-id="{$iddocumento}">
               <i class="fa fa-mail-reply"></i> Responder con documento nuevo
           </a>
           <a href="#" class="dropdown-item answer" data-id="{$iddocumento}">
               <i class="fa fa-mail-reply"></i> Responder con documento existente
           </a>
           <a href="#" class="dropdown-item viewTask" data-id="{$iddocumento}">
               <i class="fa fa-eye"></i> Tareas
           </a>
HTML;
            break;

        default:
            $options = <<<HTML
             <a href="#" class="dropdown-item addTask" data-id="{$iddocumento}">
                <i class="fa fa-plus"></i> Asignar Tarea
            </a>
            <a href="#" class="dropdown-item cancel" data-id="{$iddocumento}">
                <i class="fa fa-exclamation-triangle"></i> Anular
            </a>
HTML;
            break;
    }

    $code = <<<HTML
    <div class="dropdown">
        <button class="btn bg-institutional mx-1" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <i class="fa fa-ellipsis-v"></i>
        </button>
        <div class="dropdown-menu dropdown-menu-left bg-white" role="menu">
           {$options}
        </div>
    </div>
HTML;

    return $code;
}
