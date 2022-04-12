<?php

use App\Bundles\pqr\formatos\pqr\FtPqr;
use App\Bundles\pqr\Services\models\PqrForm;
use App\Bundles\pqr\Services\models\PqrFormField;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Saia\models\tarea\TareaEstado;
use App\Bundles\pqr\helpers\UtilitiesPqr;
use Saia\controllers\DateController;
use Saia\models\documento\Documento;
use Saia\models\busqueda\BusquedaComponente;
use Saia\models\formatos\CampoSeleccionados;
use Saia\models\Dependencia;
use App\services\GlobalContainer;
use Doctrine\DBAL\Types\Types;

include_once $_SERVER['ROOT_PATH'] . 'src/Bundles/pqr/formatos/reporteFuncionesGenerales.php';
$fileAdditionalFunctions = $_SERVER['ROOT_PATH'] . 'src/Bundles/pqr/formatos/pqr/functionsReport.php';
if (file_exists($fileAdditionalFunctions)) {
    include_once $fileAdditionalFunctions;
}

/**
 * @param int $idft
 * @author Andres Agudelo <andres.agudelo@cerok.com> 2022-04-12
 */
function setFtPqr(int $idft)
{
    $GLOBALS['FtPqr'] = UtilitiesPqr::getInstanceForFtId($idft);
}

/**
 * @return FtPqr
 * @author Andres Agudelo <andres.agudelo@cerok.com> 2022-04-12
 */
function getFtPqr(): FtPqr
{
    return $GLOBALS['FtPqr'];
}

/**
 * @param int $idft
 * @param     $numero
 * @return string
 * @author Andres Agudelo <andres.agudelo@cerok.com> 2022-04-12
 */
function viewFtPqr(int $idft, $numero): string
{
    setFtPqr($idft);
    $FtPqr = getFtPqr();

    return view((int)$FtPqr->documento_iddocumento, $numero);
}

/**
 * @return string
 * @author Andres Agudelo <andres.agudelo@cerok.com> 2022-04-12
 */
function getExpiration(): string
{
    $FtPqr = getFtPqr();
    return $FtPqr->getService()->getColorExpiration();
}

/**
 * @return string
 * @author Andres Agudelo <andres.agudelo@cerok.com> 2022-04-12
 */
function getEndDate(): string
{
    $FtPqr = getFtPqr();
    return $FtPqr->getService()->getEndDate();
}

/**
 * @return string
 * @author Andres Agudelo <andres.agudelo@cerok.com> 2022-04-12
 */
function getDaysLate(): string
{
    $FtPqr = getFtPqr();
    return $FtPqr->getService()->getDaysLate();
}

/**
 * @return string
 * @author Andres Agudelo <andres.agudelo@cerok.com> 2022-04-12
 */
function getDaysWait(): string
{
    $FtPqr = getFtPqr();
    return $FtPqr->getService()->getDaysWait();
}

/**
 * @param int $iddocumento
 * @param     $fkCampoOpciones
 * @return string
 * @author Andres Agudelo <andres.agudelo@cerok.com> 2022-04-12
 */
function getValueSysTipo(int $iddocumento, $fkCampoOpciones): string
{
    if ($fkCampoOpciones == PqrFormField::FIELD_NAME_SYS_TIPO) {
        return 'Sin Tipo';
    }

    $tipo = '';
    if ($valor = CampoSeleccionados::findColumn('valor', [
        'fk_campo_opciones' => $fkCampoOpciones,
        'fk_documento'      => $iddocumento
    ])) {
        $tipo = $valor[0];
    }

    return $tipo;
}

/**
 * @param int $iddocumento
 * @return string
 * @author Andres Agudelo <andres.agudelo@cerok.com> 2022-04-12
 */
function totalTask(int $iddocumento): string
{
    $data = UtilitiesPqr::getFinishTotalTask(new Documento($iddocumento));

    return "{$data['finish']}/{$data['total']}";
}

/**
 * @param int $idft
 * @return string
 * @author Andres Agudelo <andres.agudelo@cerok.com> 2022-04-12
 */
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
        'variable_busqueda'     => json_encode(['idft_pqr' => $idft]),
        'idbusqueda_componente' => $idbusquedaComponenteRespuesta
    ]);

    $numero = $FtPqr->getDocument()->numero;
    $answers = [];
    foreach ($records as $FtPqrRespuesta) {
        $fecha = DateController::convertDate($FtPqrRespuesta->getDocument()->fecha, DateController::PUBLIC_DATE_FORMAT);
        $answers[] = "<a class='kenlace_saia' data-enlace='$url' title='Respuestas a PQR No $numero' href='#'>{$FtPqrRespuesta->getDocument()->numero} - $fecha</a>";
    }

    return implode('<br/>', $answers);
}

/**
 * @param int $iddocumento
 * @return string
 * @author Andres Agudelo <andres.agudelo@cerok.com> 2022-04-12
 */
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

/**
 * @param int    $iddocumento
 * @param string $estado
 * @param int    $idft
 * @return string
 * @author Andres Agudelo <andres.agudelo@cerok.com> 2022-04-12
 */
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

/**
 * @param int $dependenciaId
 * @author Andres Agudelo <andres.agudelo@cerok.com> 2022-04-12
 */
function setDependencia(int $dependenciaId)
{
    $GLOBALS['Dependencia'] = new Dependencia($dependenciaId);
}

/**
 * @return Dependencia
 * @author Andres Agudelo <andres.agudelo@cerok.com> 2022-04-12
 */
function getDependencia(): Dependencia
{
    return $GLOBALS['Dependencia'];
}

/**
 * @param int $dependenciaId
 * @return string
 * @author Andres Agudelo <andres.agudelo@cerok.com> 2022-04-12
 */
function getNombreDependencia(int $dependenciaId): string
{
    setDependencia($dependenciaId);
    $Dependencia = getDependencia();

    return $Dependencia->nombre;
}

/**
 * @return QueryBuilder
 * @author Andres Agudelo <andres.agudelo@cerok.com> 2022-04-12
 */
function QbDependencia(): QueryBuilder
{
    $Dependencia = getDependencia();

    return GlobalContainer::getConnection()->createQueryBuilder()
        ->select('count(sys_dependencia) as cant')
        ->from('vpqr', 'v')
        ->where('sys_dependencia = :dependencyId')
        ->setParameter(':dependencyId', $Dependencia->getPK(), Types::INTEGER);
}

/**
 * @return string
 * @author Andres Agudelo <andres.agudelo@cerok.com> 2022-04-12
 */
function getCantidad(): string
{
    $cant = QbDependencia()->execute()->fetchOne();
    return createView(PqrForm::FILTER_TODOS, (int)$cant);
}

/**
 * @return string
 * @author Andres Agudelo <andres.agudelo@cerok.com> 2022-04-12
 */
function getPendientes(): string
{
    $cant = QbDependencia()->andWhere('sys_estado IN (:estado)')
        ->setParameter(':estado', [FtPqr::ESTADO_PROCESO, FtPqr::ESTADO_PENDIENTE], Connection::PARAM_STR_ARRAY)
        ->execute()->fetchOne();

    return createView(PqrForm::FILTER_PENDIENTES, (int)$cant);
}

/**
 * @return string
 * @author Andres Agudelo <andres.agudelo@cerok.com> 2022-04-12
 */
function getResueltas(): string
{
    $cant = QbDependencia()->andWhere('sys_estado LIKE :estado')
        ->setParameter(':estado', FtPqr::ESTADO_TERMINADO, Types::STRING)
        ->execute()->fetchOne();

    return createView(PqrForm::FILTER_RESUELTAS, (int)$cant);
}

/**
 * @return int
 * @author Andres Agudelo <andres.agudelo@cerok.com> 2022-04-12
 */
function getComponenteRepTodos(): int
{
    if (!$GLOBALS['idbusquedaComponenteRepTodos']) {
        $GLOBALS['idbusquedaComponenteRepTodos'] = BusquedaComponente::findColumn('idbusqueda_componente', [
            'nombre' => PqrForm::NOMBRE_REPORTE_TODOS
        ])[0];
    }
    return (int)$GLOBALS['idbusquedaComponenteRepTodos'];
}

/**
 * @param string $filterName
 * @param int    $cant
 * @return string
 * @author Andres Agudelo <andres.agudelo@cerok.com> 2022-04-12
 */
function createView(string $filterName, int $cant): string
{
    $Dependencia = getDependencia();
    $url = 'views/buzones/grilla.php?';
    $url .= http_build_query([
        'variable_busqueda'     => json_encode([
            'sys_dependencia' => $Dependencia->getPK(),
            'filterName'      => $filterName
        ]),
        'idbusqueda_componente' => getComponenteRepTodos()
    ]);

    return <<<HTML
    <a class='kenlace_saia' data-enlace='$url' title='PQRS' href='#'>
            <button class='btn btn-complete'>$cant</button>
    </a>
HTML;
}

/**
 * @return string
 * @author Andres Agudelo <andres.agudelo@cerok.com> 2022-04-12
 */
function filter_pqr(): string
{
    $response = "1=1";
    if (!$_REQUEST['variable_busqueda']) {
        return $response;
    }

    $params = json_decode($_REQUEST['variable_busqueda'], true);
    $sysDependencia = (int)$params['sys_dependencia'];
    if (!$sysDependencia) {
        return $response;
    }

    switch ($params['filterName']) {
        case PqrForm::FILTER_PENDIENTES:
            $response = "sys_dependencia=$sysDependencia AND sys_estado IN ('" . FtPqr::ESTADO_PENDIENTE . "','" . FtPqr::ESTADO_PROCESO . "')";
            break;
        case PqrForm::FILTER_RESUELTAS:
            $response = "sys_dependencia=$sysDependencia AND sys_estado LIKE '" . FtPqr::ESTADO_TERMINADO . "'";
            break;
        case PqrForm::FILTER_TODOS:
        default:
            $response = "sys_dependencia=$sysDependencia";
            break;
    }
    return $response;
}