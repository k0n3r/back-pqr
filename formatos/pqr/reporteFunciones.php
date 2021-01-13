<?php

$fileAdditionalFunctions = ROOT_PATH . 'src/Bundles/pqr/formatos/pqr/functionsReport.php';
if (file_exists($fileAdditionalFunctions)) {
    include_once $fileAdditionalFunctions;
}

use App\Bundles\pqr\formatos\pqr\FtPqr;
use Saia\models\tarea\TareaEstado;
use App\Bundles\pqr\helpers\UtilitiesPqr;
use Saia\controllers\DateController;
use Saia\models\documento\Documento;
use Saia\models\busqueda\BusquedaComponente;
use Saia\models\formatos\CampoSeleccionados;

function viewFtPqr(int $idft, $numero): String
{
    global $FtPqr;

    $GLOBALS['FtPqr'] = new FtPqr($idft);

    $enlace = <<<HTML
    <div class='kenlace_saia'
    enlace='views/documento/index_acordeon.php?documentId={$FtPqr->documento_iddocumento}' 
    conector='iframe'
    titulo='No Registro {$numero}'>
        <button class='btn btn-complete' style='margin:auto'>{$numero}</button>
    </div>
HTML;
    return $enlace;
}

function getExpiration(int $idft)
{
    global $FtPqr;

    return $FtPqr->getColorExpiration();
}

function getEndDate(int $idft)
{
    global $FtPqr;

    return $FtPqr->getEndDate();
}

function getDaysLate(int $idft)
{
    global $FtPqr;

    return $FtPqr->getDaysLate();
}

function getDaysWait(int $idft)
{
    global $FtPqr;

    return $FtPqr->getDaysWait();
}

function getValueSysTipo(int $iddocumento, $fkCampoOpciones)
{
    if ($fkCampoOpciones == 'sys_tipo') {
        return 'Sin Tipo';
    }

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
    global $idbusquedaComponenteRespuesta, $FtPqr;

    if (!$idbusquedaComponenteRespuesta) {
        $GLOBALS['idbusquedaComponenteRespuesta'] = BusquedaComponente::findColumn('idbusqueda_componente', [
            'nombre' => 'respuesta_pqr'
        ])[0];
    }

    $records = $FtPqr->getPqrAnswers();
    $cant = count($records);
    if (!$cant) {
        return 0;
    }

    $url = 'views/buzones/grilla.php?';
    $url .= http_build_query([
        'variable_busqueda' => json_encode(['idft_pqr' => $idft]),
        'idbusqueda_componente' => $idbusquedaComponenteRespuesta
    ]);

    $numero = $FtPqr->Documento->numero;
    $answers = [];
    foreach ($records as $FtPqrRespuesta) {
        $fecha = DateController::convertDate($FtPqrRespuesta->Documento->fecha, DateController::PUBLIC_DATE_FORMAT);
        $answers[] = "<a class='kenlace_saia' enlace='{$url}' title='Ver las respuestas' conector='iframe' titulo='Respuestas a PQR No {$numero}' href='#'>{$FtPqrRespuesta->Documento->numero} - {$fecha}</a>";
    }

    return implode('<br/>', $answers);
}

function getResponsible(int $iddocumento)
{
    $tareas = (new Documento($iddocumento))->getService()->getTasks();
    if (!$tareas) {
        return '';
    }

    $responsible = [];
    foreach ($tareas as $Tarea) {
        if ($Tarea->getService()->getState()->valor == TareaEstado::CANCELADA) {
            continue;
        }

        $funcionarios = $Tarea->getService()->getManagers();
        foreach ($funcionarios as $Funcionario) {
            $responsible[$Funcionario->getPK()] = $Funcionario->getName();
        }
    }

    return implode('<br/>', $responsible);
}

function options(int $iddocumento, string $estado, int $idft): string
{
    switch ($estado) {
        case FtPqr::ESTADO_PROCESO:
            $options = <<<HTML
            <a href="#" class="dropdown-item addTask" data-id="{$iddocumento}" data-idft="{$idft}">
                <i class="fa fa-plus"></i> Asignar tarea
            </a>
            <a href="#" class="dropdown-item viewTask" data-id="{$iddocumento}" data-idft="{$idft}">
               <i class="fa fa-eye"></i> Tareas
           </a>
           <a href="#" class="dropdown-item edit" data-id="{$iddocumento}" data-idft="{$idft}">
                <i class="fa fa-edit"></i> Editar tipo
            </a>
            <a href="#" class="dropdown-item history" data-id="{$iddocumento}" data-idft="{$idft}">
                <i class="fa fa-history"></i> Historial
            </a>
            <a href="#" class="dropdown-item answer" data-id="{$iddocumento}" data-idft="{$idft}">
               <i class="fa fa-mail-reply"></i> Responder
           </a>

HTML;
            break;

        case FtPqr::ESTADO_TERMINADO:
            $options = <<<HTML
            <a href="#" class="dropdown-item addTask" data-id="{$iddocumento}" data-idft="{$idft}">
                <i class="fa fa-plus"></i> Asignar tarea
            </a>
            <a href="#" class="dropdown-item viewTask" data-id="{$iddocumento}" data-idft="{$idft}">
               <i class="fa fa-eye"></i> Tareas
           </a>
            <a href="#" class="dropdown-item history" data-id="{$iddocumento}" data-idft="{$idft}">
                <i class="fa fa-history"></i> Historial
            </a>
            <a href="#" class="dropdown-item answer" data-id="{$iddocumento}" data-idft="{$idft}">
               <i class="fa fa-mail-reply"></i> Responder
           </a>

HTML;
            break;

        case FtPqr::ESTADO_PENDIENTE:
        default:
            $options = <<<HTML
            <a href="#" class="dropdown-item addTask" data-id="{$iddocumento}" data-idft="{$idft}">
                <i class="fa fa-plus"></i> Asignar tarea
            </a>
           <a href="#" class="dropdown-item edit" data-id="{$iddocumento}" data-idft="{$idft}">
                <i class="fa fa-edit"></i> Editar tipo
            </a>
            <a href="#" class="dropdown-item history" data-id="{$iddocumento}" data-idft="{$idft}">
                <i class="fa fa-history"></i> Historial
            </a>
            <a href="#" class="dropdown-item answer" data-id="{$iddocumento}" data-idft="{$idft}">
               <i class="fa fa-mail-reply"></i> Responder
           </a>
            <a href="#" class="dropdown-item finish" data-id="{$iddocumento}" data-idft="{$idft}">
                <i class="fa fa-check"></i> Terminar
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
