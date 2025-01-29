<?php

use App\Bundles\pqr\formatos\pqr\FtPqr;
use App\Bundles\pqr\Services\FtPqrService;
use App\Bundles\pqr\Services\models\PqrForm;
use App\Bundles\pqr\Services\models\PqrFormField;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Saia\controllers\SessionController;
use Saia\models\busqueda\BusquedaFiltroTemp;
use Saia\models\tarea\TareaEstado;
use App\Bundles\pqr\helpers\UtilitiesPqr;
use Saia\controllers\DateController;
use Saia\models\documento\Documento;
use Saia\models\busqueda\BusquedaComponente;
use Saia\models\formatos\CampoSeleccionados;
use Saia\models\Dependencia;
use App\services\GlobalContainer;
use Doctrine\DBAL\Types\Types;
use Saia\models\vistas\VfuncionarioDc;

include_once $_SERVER['ROOT_PATH'] . 'src/Bundles/pqr/formatos/reporteFuncionesGenerales.php';
$fileAdditionalFunctions = $_SERVER['ROOT_PATH'] . 'src/Bundles/pqr/formatos/pqr/functionsReport.php';
if (file_exists($fileAdditionalFunctions)) {
    include_once $fileAdditionalFunctions;
}

$fileAdditionalFunctions = $_SERVER['ROOT_PATH'] . 'src/Bundles/client/pqr/functionsReportPqr.php';
if (file_exists($fileAdditionalFunctions)) {
    include_once $fileAdditionalFunctions;
}

/**
 * @param int $idft
 * @author Andres Agudelo <andres.agudelo@cerok.com> 2022-04-12
 */
function setFtPqr(int $idft): void
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
 * Obtiene la experiencia en gestion de la ultima
 * calificacion realizada
 *
 * @return string
 * @author Andres Agudelo <andres.agudelo@cerok.com> 2022-11-10
 */
function qualificationGest(): string
{
    $FtPqr = getFtPqr();
    $FtPqrCalificacion = $FtPqr->getLastCalificacion();

    return $FtPqrCalificacion ? $FtPqrCalificacion->getFieldValue('experiencia_gestion') : '-';
}

/**
 * Obtiene la experiencia en servicio de la ultima
 * calificacion realizada
 *
 * @return string
 * @author Andres Agudelo <andres.agudelo@cerok.com> 2022-11-10
 */
function qualificationServ(): string
{
    $FtPqr = getFtPqr();
    $FtPqrCalificacion = $FtPqr->getLastCalificacion();

    return $FtPqrCalificacion ? $FtPqrCalificacion->getFieldValue('experiencia_servicio') : '-';
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
    $options = match ($estado) {
        FtPqr::ESTADO_PROCESO => <<<HTML
            <a href="#" class="dropdown-item addTask" data-id="$iddocumento" data-idft="$idft">
                <i class="fa fa-plus"></i> <span data-i18n="pqr.asignar_tarea">Asignar tarea</span>
            </a>
            <a href="#" class="dropdown-item viewTask" data-id="$iddocumento" data-idft="$idft">
               <i class="fa fa-eye"></i> <span data-i18n="g.tareas">Tareas</span>
           </a>
           <a href="#" class="dropdown-item edit" data-id="$iddocumento" data-idft="$idft">
                <i class="fa fa-edit"></i> <span data-i18n="pqr.validar_pqr">Validar PQRSF</span>
            </a>
           <a href="#" class="dropdown-item editUser" data-id="$iddocumento" data-idft="$idft">
                <i class="fa fa-user"></i> <span data-i18n="pqr.datos_remitente">Datos remitente</span>
            </a>
            <a href="#" class="dropdown-item history" data-id="$iddocumento" data-idft="$idft">
                <i class="fa fa-history"></i> <span data-i18n="pqr.historial">Historial</span>
            </a>
            <a href="#" class="dropdown-item answer" data-id="$iddocumento" data-idft="$idft">
               <i class="fa fa-mail-reply"></i> <span data-i18n="pqr.responder">Responder</span>
           </a>

HTML,
        FtPqr::ESTADO_TERMINADO => <<<HTML
            <a href="#" class="dropdown-item addTask" data-id="$iddocumento" data-idft="$idft">
                <i class="fa fa-plus"></i> <span data-i18n="pqr.asignar_tarea">Asignar tarea</span>
            </a>
            <a href="#" class="dropdown-item viewTask" data-id="$iddocumento" data-idft="$idft">
               <i class="fa fa-eye"></i> <span data-i18n="g.tareas">Tareas</span>
           </a>
            <a href="#" class="dropdown-item history" data-id="$iddocumento" data-idft="$idft">
                <i class="fa fa-history"></i> <span data-i18n="pqr.historial">Historial</span>
            </a>
            <a href="#" class="dropdown-item answer" data-id="$iddocumento" data-idft="$idft">
               <i class="fa fa-mail-reply"></i> <span data-i18n="pqr.responder">Responder</span>
           </a>

HTML,
        default => <<<HTML
            <a href="#" class="dropdown-item addTask" data-id="$iddocumento" data-idft="$idft">
                <i class="fa fa-plus"></i> <span data-i18n="pqr.asignar_tarea">Asignar tarea</span>
            </a>
           <a href="#" class="dropdown-item edit" data-id="$iddocumento" data-idft="$idft">
                <i class="fa fa-edit"></i> <span data-i18n="pqr.validar_pqr">Validar PQRSF</span>
            </a>
           <a href="#" class="dropdown-item editUser" data-id="$iddocumento" data-idft="$idft">
                <i class="fa fa-user"></i> <span data-i18n="pqr.datos_remitente">Datos remitente</span>
            </a>
            <a href="#" class="dropdown-item history" data-id="$iddocumento" data-idft="$idft">
                <i class="fa fa-history"></i> <span data-i18n="pqr.historial">Historial</span>
            </a>
            <a href="#" class="dropdown-item answer" data-id="$iddocumento" data-idft="$idft">
               <i class="fa fa-mail-reply"></i> <span data-i18n="pqr.responder">Responder</span>
           </a>
            <a href="#" class="dropdown-item finish" data-id="$iddocumento" data-idft="$idft">
                <i class="fa fa-check"></i> <span data-i18n="pqr.terminar">Terminar</span>
            </a>
            <a href="#" class="dropdown-item cancel" data-id="$iddocumento" data-idft="$idft">
                <i class="fa fa-exclamation-triangle"></i> <span data-i18n="pqr.anular">Anular</span>
            </a>
HTML,
    };

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
function setDependencia(int $dependenciaId): void
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
        ->setParameter('dependencyId', $Dependencia->getPK(), ParameterType::INTEGER);

}

function filterByFiltroTemp(): string
{
    if (isset($GLOBALS['whereFiltroTemp'])) {
        return $GLOBALS['whereFiltroTemp'];
    }

    $where = '';
    $idBusquedaFiltroTemp = (int)$_REQUEST['idbusqueda_filtro_temp'];
    if ($idBusquedaFiltroTemp) {
        $BusquedaFiltroTemp = new BusquedaFiltroTemp($idBusquedaFiltroTemp);
        $where = $BusquedaFiltroTemp->detalle;

    }
    return $GLOBALS['whereFiltroTemp'] = $where;
}


/**
 * @return string
 * @author Andres Agudelo <andres.agudelo@cerok.com> 2022-04-12
 */
function getCantidad(): string
{
    $Qb = QbDependencia();
    if ($where = filterByFiltroTemp()) {
        $Qb->andWhere($where);
    }
    $cant = $Qb->executeQuery()->fetchOne();

    return createView(PqrForm::FILTER_TODOS, (int)$cant);
}

/**
 * @return string
 * @author Andres Agudelo <andres.agudelo@cerok.com> 2022-04-12
 */
function getPendientes(): string
{
    $Qb = QbDependencia()->andWhere('sys_estado IN (:estado)')
        ->setParameter('estado', [FtPqr::ESTADO_PROCESO, FtPqr::ESTADO_PENDIENTE], ArrayParameterType::INTEGER);

    if ($where = filterByFiltroTemp()) {
        $Qb->andWhere($where);
    }

    $cant = $Qb->executeQuery()->fetchOne();

    return createView(PqrForm::FILTER_PENDIENTES, (int)$cant);
}

/**
 * @return string
 * @author Andres Agudelo <andres.agudelo@cerok.com> 2022-04-12
 */
function getResueltas(): string
{
    $Qb = QbDependencia()->andWhere('sys_estado LIKE :estado')
        ->setParameter('estado', FtPqr::ESTADO_TERMINADO);

    if ($where = filterByFiltroTemp()) {
        $Qb->andWhere($where);
    }
    $cant = $Qb->executeQuery()->fetchOne();

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
        'idbusqueda_filtro_temp' => (int)$_REQUEST['idbusqueda_filtro_temp'],
        'idbusqueda_componente'  => getComponenteRepTodos(),
        'variable_busqueda'      => json_encode([
            'sys_dependencia' => $Dependencia->getPK(),
            'filterName'      => $filterName
        ])
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

    return match ($params['filterName']) {
        PqrForm::FILTER_PENDIENTES => "sys_dependencia=$sysDependencia AND sys_estado IN ('" . FtPqr::ESTADO_PENDIENTE . "','" . FtPqr::ESTADO_PROCESO . "')",
        PqrForm::FILTER_RESUELTAS => "sys_dependencia=$sysDependencia AND sys_estado LIKE '" . FtPqr::ESTADO_TERMINADO . "'",
        default => "sys_dependencia=$sysDependencia",
    };
}

/**
 * Si esta activo, filtra de la siguiente manera:
 * 1. El funcionario logado si tiene la funcion "FUNCTION_ADMIN_PQR" podra ver todas las pqr
 * 2. El funcionario logado si tiene la funcion "FUNCTION_ADMIN_DEP_PQR" podra ver todas las pqr que pertenezca
 * a la dependencia a las cuales tiene asignada bajo el rol como las dependencias hijas de dichos roles
 * 3. Si no tiene ninguna funcion vinculada solo padre ver las pqr a las cuales se le hayan asignado
 * por tarea
 *
 * @param string $nameReport
 * @return string
 * @author Andres Agudelo <andres.agudelo@cerok.com> 2022-07-01
 */
function filter_pqr_admin(string $nameReport): string
{
    $nameReport = strtoupper($nameReport);

    $PqrForm = PqrForm::getInstance();
    $PqrFormField = $PqrForm->getRow('sys_dependencia');

    if (!$PqrFormField || !$PqrForm->enable_filter_dep) {
        return '';
    }

    $Funcionario = SessionController::getUser();

    $isAdmin = $Funcionario->getService()->hasFunction(FtPqrService::FUNCTION_ADMIN_PQR);
    if ($isAdmin) {
        return '';
    }

    $subconsulta = "SELECT DISTINCT iddocumento FROM vpqr v JOIN tarea t ON v.iddocumento = t.relacion_id JOIN tarea_funcionario tf on tf.fk_tarea=t.idtarea WHERE v.sys_estado='$nameReport' AND t.relacion=1 AND tf.tipo=1 AND tf.estado=1 AND tf.externo=0 AND tf.usuario = {$Funcionario->getPK()}";

    $isAdminDep = $Funcionario->getService()->hasFunction(FtPqrService::FUNCTION_ADMIN_DEP_PQR);
    if (!$isAdminDep) {
        return " AND v.iddocumento IN ($subconsulta)";
    }

    $records = VfuncionarioDc::getBasicQb()
        ->select('iddependencia')
        ->distinct()
        ->andWhere('idfuncionario =:idfuncionario')
        ->setParameter('idfuncionario', $Funcionario->getPK(), ParameterType::INTEGER)
        ->executeQuery()->fetchAllAssociative();

    if (!$records) {
        return '1=0';
    }

    $ids = [];
    foreach ($records as $id) {
        $ids[] = $id['iddependencia'];
        $children = (new Dependencia($id['iddependencia']))->getChildren();
        foreach ($children as $DependenciaChild) {
            $ids[] = $DependenciaChild->getPK();
        }
    }

    return " AND (sys_dependencia IN (" . implode(',', array_unique($ids)) . ") OR v.iddocumento IN ($subconsulta))";
}