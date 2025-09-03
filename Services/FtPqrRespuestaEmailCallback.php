<?php

namespace App\Bundles\pqr\Services;

use App\Bundles\pqr\helpers\UtilitiesPqr;
use App\services\correo\SendEmailCallback;
use App\services\GlobalContainer;
use RuntimeException;

class FtPqrRespuestaEmailCallback implements SendEmailCallback
{

    public function execute(array $params): void
    {
        switch ($params['option']) {
            case FtPqrRespuestaService::OPTION_EMAIL_RESPUESTA:
            case FtPqrRespuestaService::OPTION_EMAIL_CALIFICACION:

                $FtPqrRespuesta = UtilitiesPqr::getInstanceForFtIdPqrRespuesta($params['idft']);

                if (!$FtPqrRespuesta->getService()->saveHistory($params['descripcion'], $params['tipo'])) {
                    $trans = GlobalContainer::getTranslator()->trans("no_fue_posible_guardar_historial");
                    throw new RuntimeException($trans);
                }
                break;
        }
    }

}