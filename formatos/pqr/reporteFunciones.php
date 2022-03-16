<?php

use App\Bundles\pqr\formatos\pqr\FtPqr;
use App\Bundles\pqr\Services\models\PqrFormField;
use Saia\models\tarea\TareaEstado;
use App\Bundles\pqr\helpers\UtilitiesPqr;
use Saia\controllers\DateController;
use Saia\models\documento\Documento;
use Saia\models\busqueda\BusquedaComponente;
use Saia\models\formatos\CampoSeleccionados;
use Saia\models\Dependencia;
use App\services\GlobalContainer;
use Doctrine\DBAL\Types\Types;

$fileAdditionalFunctions = $_SERVER['ROOT_PATH'] . 'src/Bundles/pqr/formatos/pqr/functionsReport.php';
if (file_exists($fileAdditionalFunctions)) {
    include_once $fileAdditionalFunctions;
}

function setFtPqr(int $idft)
{
    $GLOBALS['FtPqr'] = UtilitiesPqr::getInstanceForFtId($idft);
}

function getFtPqr(): FtPqr
{
    return $GLOBALS['FtPqr'];
}

function viewFtPqr(int $idft, $numero): string
{
    setFtPqr($idft);
    $FtPqr = getFtPqr();

    return <<<HTML
    <div class='kenlace_saia'
    enlace='views/documento/index_acordeon.php?documentId=$FtPqr->documento_iddocumento' 
    conector='iframe'
    titulo='No Registro $numero'>
        <button class='btn btn-complete' style='margin:auto'>$numero</button>
    </div>
HTML;
}

function getExpiration(): string
{
    $FtPqr = getFtPqr();
    return $FtPqr->getService()->getColorExpiration();
}

function getEndDate(): string
{
    $FtPqr = getFtPqr();
    return $FtPqr->getService()->getEndDate();
}

function getDaysLate(): string
{
    $FtPqr = getFtPqr();
    return $FtPqr->getService()->getDaysLate();
}

function getDaysWait(): string
{
    $FtPqr = getFtPqr();
    return $FtPqr->getService()->getDaysWait();
}

function getValueSysTipo(int $iddocumento, $fkCampoOpciones): string
{
    if ($fkCampoOpciones == PqrFormField::FIELD_NAME_SYS_TIPO) {
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
    global $idbusquedaComponenteRespuesta;

    $FtPqr = getFtPqr();

    if (!$idbusquedaComponenteRespuesta) {
        $GLOBALS['idbusquedaComponenteRespuesta'] = BusquedaComponente::findColumn('idbusqueda_componente', [
            'nombre' => 'respuesta_pqr'
        ])[0];
    }

    $records = $FtPqr->getService()->getPqrAnswers();
    $cant = count($records);
    if (!$cant) {
        return 0;
    }

    $url = 'views/buzones/grilla.php?';
    $url .= http_build_query([
        'variable_busqueda' => json_encode(['idft_pqr' => $idft]),
        'idbusqueda_componente' => $idbusquedaComponenteRespuesta
    ]);

    $numero = $FtPqr->getDocument()->numero;
    $answers = [];
    foreach ($records as $FtPqrRespuesta) {
        $fecha = DateController::convertDate($FtPqrRespuesta->getDocument()->fecha, DateController::PUBLIC_DATE_FORMAT);
        $answers[] = "<a class='kenlace_saia' enlace='$url' title='Ver las respuestas' conector='iframe' titulo='Respuestas a PQR No $numero' href='#'>{$FtPqrRespuesta->getDocument()->numero} - $fecha</a>";
    }

    return implode('<br/>', $answers);
}

function getResponsible(int $iddocumento): string
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
            <a href="#" class="dropdown-item addTask" data-id="$iddocumento" data-idft="$idft">
                <i class="fa fa-plus"></i> Asignar tarea
            </a>
            <a href="#" class="dropdown-item viewTask" data-id="$iddocumento" data-idft="$idft">
               <i class="fa fa-eye"></i> Tareas
           </a>
           <a href="#" class="dropdown-item edit" data-id="$iddocumento" data-idft="$idft">
                <i class="fa fa-edit"></i> Validar PQRSF
            </a>
            <a href="#" class="dropdown-item history" data-id="$iddocumento" data-idft="$idft">
                <i class="fa fa-history"></i> Historial
            </a>
            <a href="#" class="dropdown-item answer" data-id="$iddocumento" data-idft="$idft">
               <i class="fa fa-mail-reply"></i> Responder
           </a>

HTML;
            break;

        case FtPqr::ESTADO_TERMINADO:
            $options = <<<HTML
            <a href="#" class="dropdown-item addTask" data-id="$iddocumento" data-idft="$idft">
                <i class="fa fa-plus"></i> Asignar tarea
            </a>
            <a href="#" class="dropdown-item viewTask" data-id="$iddocumento" data-idft="$idft">
               <i class="fa fa-eye"></i> Tareas
           </a>
            <a href="#" class="dropdown-item history" data-id="$iddocumento" data-idft="$idft">
                <i class="fa fa-history"></i> Historial
            </a>
            <a href="#" class="dropdown-item answer" data-id="$iddocumento" data-idft="$idft">
               <i class="fa fa-mail-reply"></i> Responder
           </a>

HTML;
            break;

        case FtPqr::ESTADO_PENDIENTE:
        default:
            $options = <<<HTML
            <a href="#" class="dropdown-item addTask" data-id="$iddocumento" data-idft="$idft">
                <i class="fa fa-plus"></i> Asignar tarea
            </a>
           <a href="#" class="dropdown-item edit" data-id="$iddocumento" data-idft="$idft">
                <i class="fa fa-edit"></i> Validar PQRSF
            </a>
            <a href="#" class="dropdown-item history" data-id="$iddocumento" data-idft="$idft">
                <i class="fa fa-history"></i> Historial
            </a>
            <a href="#" class="dropdown-item answer" data-id="$iddocumento" data-idft="$idft">
               <i class="fa fa-mail-reply"></i> Responder
           </a>
            <a href="#" class="dropdown-item finish" data-id="$iddocumento" data-idft="$idft">
                <i class="fa fa-check"></i> Terminar
            </a>
            <a href="#" class="dropdown-item cancel" data-id="$iddocumento" data-idft="$idft">
                <i class="fa fa-exclamation-triangle"></i> Anular
            </a>
HTML;
            break;
    }

    return <<<HTML
    <div class="dropdown">
        <button class="btn bg-institutional mx-1" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <i class="fa fa-ellipsis-v"></i>
        </button>
        <div class="dropdown-menu dropdown-menu-left bg-white" role="menu">
           $options
        </div>
    </div>
HTML;

}

function getDependencia($dependencia){
    if($dependencia != ""){
        $Dependencia = new Dependencia($dependencia);
    }
    return $Dependencia->nombre;
}

function getCantidad($dependencia){
    $FtPqrRecord = FtPqr::findAllByAttributes([
        'sys_dependencia' => $dependencia
    ]);
    $documentoList = [];
    $cantidadDependencias = count($FtPqrRecord);
    foreach ($FtPqrRecord as $FtPqr) {
        $documentoList[] = $FtPqr->documento_iddocumento;
    }
    global $idbusquedaComponenteRespuesta;
    if (!$idbusquedaComponenteRespuesta) {
        $GLOBALS['idbusquedaComponenteRespuesta'] = BusquedaComponente::findColumn('idbusqueda_componente', [
            'nombre' => 'rep_total_pqr_depen'
        ])[0];
    }
    $documentList = implode(",", $documentoList);
    $url = 'views/buzones/grilla.php?';
    $url .= http_build_query([
        'variable_busqueda' => json_encode(['documentoList' => $documentList]),
        'idbusqueda_componente' => $idbusquedaComponenteRespuesta
    ]);
    return <<<HTML
    <a class='kenlace_saia' enlace='$url' title='PQRS' conector='iframe' titulo='PQRS' href='#'>
            <button class='btn btn-complete' style='margin:auto'>$cantidadDependencias</button>
    </a>
HTML;
}

function getPendientes($dependencia){
    $PqrRespuestaRecords = GlobalContainer::getConnection()->createQueryBuilder()
        ->select("vr.ft_pqr")
        ->from('vpqr', 'vp')
        ->join('vp', 'vpqr_respuesta', 'vr', 'vp.idft = vr.ft_pqr')
        ->where("vp.sys_dependencia = :dependencia")
        ->setParameter(":dependencia", $dependencia, Types::INTEGER)
        ->execute()
        ->fetchAllAssociative();
        
    $respuestaList = [];
    
    foreach ($PqrRespuestaRecords as $PqrRespuesta) {
        $respuestaList[] = $PqrRespuesta['ft_pqr'];
    }
    $respuesta = implode(',', $respuestaList);
    if($respuesta){
        $PqrPendientesRecords = GlobalContainer::getConnection()->createQueryBuilder()
            ->select("vp.iddocumento")
            ->from('vpqr', 'vp')
            ->where("vp.idft NOT IN(" . $respuesta .")")
            ->andWhere("vp.sys_dependencia = :dependencia")
            ->setParameter(":dependencia", $dependencia, Types::INTEGER)
            ->execute()
            ->fetchAllAssociative();
    }else{
        $PqrPendientesRecords = GlobalContainer::getConnection()->createQueryBuilder()
        ->select("vp.iddocumento")
        ->from('vpqr', 'vp')
        ->where("vp.sys_dependencia = :dependencia")
        ->setParameter(":dependencia", $dependencia, Types::INTEGER)
        ->execute()
        ->fetchAllAssociative();
    }
    
    $cantidadPendientes = count($PqrPendientesRecords);
    $documentoList = [];
    foreach ($PqrPendientesRecords as $PqrPendientes) {
        $documentoList[] = $PqrPendientes['iddocumento'];
    }

    global $idbusquedaComponenteRespuesta;
    if (!$idbusquedaComponenteRespuesta) {
        $GLOBALS['idbusquedaComponenteRespuesta'] = BusquedaComponente::findColumn('idbusqueda_componente', [
            'nombre' => 'rep_total_pqr_depen'
        ])[0];
    }
    $documentList = implode(",", $documentoList);
    $url = 'views/buzones/grilla.php?';
    $url .= http_build_query([
        'variable_busqueda' => json_encode(['documentoList' => $documentList]),
        'idbusqueda_componente' => $idbusquedaComponenteRespuesta
    ]);
    return <<<HTML
    <a class='kenlace_saia' enlace='$url' title='PQRS' conector='iframe' titulo='PQRS' href='#'>
            <button class='btn btn-complete' style='margin:auto'>$cantidadPendientes</button>
    </a>
HTML;
}

function getResueltas($dependencia){
    $PqrTerminadoRecords = GlobalContainer::getConnection()->createQueryBuilder()
        ->select("vp.iddocumento")
        ->from('vpqr', 'vp')
        ->join('vp', 'vpqr_respuesta', 'vr', 'vp.idft = vr.ft_pqr')
        ->where("vp.sys_dependencia = :dependencia")
        ->setParameter(":dependencia", $dependencia, Types::INTEGER)
        ->execute()
        ->fetchAllAssociative();
    $cantidadTerminado = count($PqrTerminadoRecords);
    $documentoList = [];
    foreach ($PqrTerminadoRecords as $PqrTerminado) {
        $documentoList[] = $PqrTerminado['iddocumento'];
    }
    global $idbusquedaComponenteRespuesta;
    if (!$idbusquedaComponenteRespuesta) {
        $GLOBALS['idbusquedaComponenteRespuesta'] = BusquedaComponente::findColumn('idbusqueda_componente', [
            'nombre' => 'rep_total_pqr_depen'
        ])[0];
    }
    $documentList = implode(",", $documentoList);
    $url = 'views/buzones/grilla.php?';
    $url .= http_build_query([
        'variable_busqueda' => json_encode(['documentoList' => $documentList]),
        'idbusqueda_componente' => $idbusquedaComponenteRespuesta
    ]);
    return <<<HTML
    <a class='kenlace_saia' enlace='$url' title='PQRS' conector='iframe' titulo='PQRS' href='#'>
            <button class='btn btn-complete' style='margin:auto'>$cantidadTerminado</button>
    </a>
HTML;

}

function filter_pqr(){
    
    if ($_REQUEST['variable_busqueda']) {
        $params = json_decode($_REQUEST['variable_busqueda'], true);
        $idft = $params['documentoList'];
        
        if ($idft) {
            return "v.iddocumento in($idft)";
        }
    }

    return 'v.iddocumento=""';
}

function verDocumento(int $iddocumento, int $numero){
        return <<<HTML
        <div class='kenlace_saia'
        enlace='views/documento/index_acordeon.php?documentId=$iddocumento'
        conector='iframe' titulo='No Registro $numero'>
        <button class='btn btn-complete' style='margin:auto'>$numero</button>
        </div>
HTML;
    }