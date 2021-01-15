<?php

use Saia\controllers\documento\RutaService;
use Saia\models\busqueda\BusquedaComponente;
use App\Bundles\pqr\formatos\pqr_respuesta\FtPqrRespuesta;

/**
 * Obtiene el filtro por PQR
 *
 * @return string
 * @author Andres Agudelo <andres.agudelo@cerok.com>
 * @date 2020
 */
function filter_answer_by_pqr(): string
{
    $idft = $_REQUEST['idft_pqr'];
    if ($idft) {
        return "ft_pqr={$idft}";
    }
    return '1=1';
}

/**
 * obtiene el nombre del responsable
 *
 * @param integer $iddocumento
 * @return string
 * @author Andres Agudelo <andres.agudelo@cerok.com>
 * @date 2020
 * 
 */
function getResponsable(int $iddocumento): string
{
    $FtPqrRespuesta = FtPqrRespuesta::findByDocumentId($iddocumento);
    $GLOBALS['FtPqrRespuesta'] = $FtPqrRespuesta;

    $Funcionario = RutaService::getApprover($iddocumento);

    return $Funcionario ? $Funcionario->getName() : '';
}

/**
 * Muestra el enlace hacia el reporte de Calificaciones
 *
 * @param integer $idft
 * @return string
 * @author Andres Agudelo <andres.agudelo@cerok.com>
 * @date 2020
 * 
 */
function viewCalificacion(int $idft): string
{
    global $FtPqrRespuesta, $idbusquedaComponenteCalificacion;

    $records = $FtPqrRespuesta->getFtPqrCalificacion();

    if (!$cant = count($records)) {
        $email = $FtPqrRespuesta->Tercero->correo ?? '';
        return '<a class="requestSurvey" href="#" data-email="' . $email . '" data-idft="' . $idft . '">SOLICITAR CALIFICACIÓN</a>';
    }

    if (!$idbusquedaComponenteCalificacion) {
        $GLOBALS['idbusquedaComponenteCalificacion'] = BusquedaComponente::findColumn(
            'idbusqueda_componente',
            [
                'nombre' => 'calificacion_pqr'
            ]
        )[0];
    }

    $url = 'views/buzones/grilla.php?';
    $url .= http_build_query([
        'variable_busqueda' => json_encode(['idft_pqr_respuesta' => $idft]),
        'idbusqueda_componente' => $idbusquedaComponenteCalificacion
    ]);
    $numero = $FtPqrRespuesta->Documento->numero;
    $nombreFormato = $FtPqrRespuesta->Formato->etiqueta;

    $enlace = <<<HTML
    <div class='kenlace_saia'
    enlace='{$url}' 
    conector='iframe'
    titulo='Calificación a {$nombreFormato} No {$numero}'>
        <button class='btn btn-complete' style='margin:auto'>{$cant}</button>
    </div>
HTML;
    return $enlace;
}
