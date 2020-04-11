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

use Saia\controllers\CryptController;
use Saia\Pqr\formatos\pqr_respuesta\FtPqrRespuesta;

$Response = (object) [
    'message' => '',
    'success' => 0,
];

try {
    if (!$_REQUEST['dataCrypt']) {
        throw new Exception("Error Processing Request");
    }
    $data = json_decode(CryptController::decrypt($_REQUEST['dataCrypt']), true);

    $FtPqrRespuesta = new FtPqrRespuesta($data['ft_pqr_respuesta']);
    if ($FtPqrRespuesta->FtPqrCalificacion) {
        throw new Exception("Ya se ha calificado esta solicitud", 200);
    }

    $Response->data = $data;
    $Response->success = 1;
} catch (Throwable $th) {
    $Response->message = $th->getMessage();
    $Response->code = $th->getCode();
}

echo json_encode($Response);
