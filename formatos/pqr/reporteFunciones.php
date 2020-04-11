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

use Saia\Pqr\formatos\pqr\FtPqr;
use Saia\Pqr\Helpers\UtilitiesPqr;
use Saia\models\documento\Documento;
use Saia\models\busqueda\BusquedaComponente;
use Saia\models\formatos\CampoSeleccionados;

function getValueSysTipo(int $iddocumento, int $fkCampoOpciones)
{
    $tipo = '';
    if ($valor = CampoSeleccionados::findColumn('valor', [
        'fk_campo_opciones' => $fkCampoOpciones,
        'fk_documento' => $iddocumento
    ])) {
        $tipo = $valor[0];
    }

    return $tipo;
}

function totalTask(int $iddocumento): string
{
    $data = UtilitiesPqr::getFinishTotalTask(new Documento($iddocumento));

    return "{$data['finish']}/{$data['total']}";
}

function totalAnswers(int $idft): string
{
    global $idbusquedaComponenteRespuesta;

    if (!$idbusquedaComponenteRespuesta) {
        $GLOBALS['idbusquedaComponenteRespuesta'] = BusquedaComponente::findColumn('idbusqueda_componente', [
            'nombre' => 'respuesta_pqr'
        ])[0];
    }

    $FtPqr = new FtPqr($idft);
    $cant = count($FtPqr->getPqrAnswers());
    if (!$cant) {
        return 0;
    }

    $url = 'views/buzones/grilla.php?';
    $url .= http_build_query([
        'variable_busqueda' => json_encode(['idft_pqr' => $idft]),
        'idbusqueda_componente' => $idbusquedaComponenteRespuesta
    ]);

    $numero = $FtPqr->Documento->numero;

    $enlace = <<<HTML
    <div class='kenlace_saia'
    enlace='{$url}' 
    conector='iframe'
    titulo='Respuestas a PQR No {$numero}'>
        <button class='btn btn-complete' style='margin:auto'>{$cant}</button>
    </div>
HTML;
    return $enlace;
}

function options(int $iddocumento, string $estado, int $idft): string
{
    switch ($estado) {
        case FtPqr::ESTADO_PROCESO:
            $options = <<<HTML
            <a href="#" class="dropdown-item addTask" data-id="{$iddocumento}" data-idft="{$idft}">
               <i class="fa fa-plus"></i> Asignar Tarea
           </a>
           <a href="#" class="dropdown-item viewTask" data-id="{$iddocumento}" data-idft="{$idft}">
               <i class="fa fa-eye"></i> Tareas
           </a>
HTML;
            break;

        case FtPqr::ESTADO_TERMINADO:
            $options = <<<HTML
            <a href="#" class="dropdown-item answer" data-id="{$iddocumento}" data-idft="{$idft}">
               <i class="fa fa-mail-reply"></i> Responder
           </a>
           <!-- <a href="#" class="dropdown-item answer2" data-id="{$iddocumento}" data-idft="{$idft}">
               <i class="fa fa-mail-reply"></i> Responder con documento existente
           </a> -->
           <a href="#" class="dropdown-item viewTask" data-id="{$iddocumento}" data-idft="{$idft}">
               <i class="fa fa-eye"></i> Tareas
           </a>
HTML;
            break;

        default:
            $options = <<<HTML
             <a href="#" class="dropdown-item addTask" data-id="{$iddocumento}" data-idft="{$idft}">
                <i class="fa fa-plus"></i> Asignar Tarea
            </a>
            <a href="#" class="dropdown-item cancel" data-id="{$iddocumento}" data-idft="{$idft}">
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
