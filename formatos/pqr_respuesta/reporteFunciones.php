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

use Saia\models\ruta\Ruta;
use Saia\Pqr\formatos\pqr_respuesta\FtPqrRespuesta;

function filter_answer_by_pqr()
{
    $idft = $_REQUEST['idft_pqr'];
    if ($idft) {
        return "ft_pqr={$idft}";
    }
    return '1=1';
}

function getResponsable(int $iddocumento)
{
    $FtPqrRespuesta = FtPqrRespuesta::findByDocumentId($iddocumento);
    $GLOBALS['FtPqrRespuesta'] = $FtPqrRespuesta;

    $Aprobador = Ruta::lastRouteFinished($iddocumento);
    return $Aprobador->getName();
}


function viewCalificacion(int $idft)
{
    global $FtPqrRespuesta;

    if ($FtPqrCalificacion = $FtPqrRespuesta->getFtPqrCalificacion()) {
        $Documento = $FtPqrCalificacion->Documento;
        return view($Documento->getPK(), $Documento->numero);
    }

    return 'PENDIENTE';
}
