<?php

namespace App\Bundles\pqr\Services;

use App\Bundles\pqr\formatos\pqr_respuesta\FtPqrRespuesta;
use App\services\correo\SendEmailCallback;
use App\services\exception\SaiaException;

class FtPqrRespuestaEmailCallback implements SendEmailCallback
{

    public function execute(array $params): void
    {
        switch ($params['option']) {
            case FtPqrRespuestaService::OPTION_EMAIL_RESPUESTA:
            case FtPqrRespuestaService::OPTION_EMAIL_CALIFICACION:

                $FtPqrRespuesta = new FtPqrRespuesta($params['idft']);

                if (!$FtPqrRespuesta->getService()->saveHistory($params['descripcion'], $params['tipo'])) {
                    throw new SaiaException("No fue posible guardar en el historial.");
                }
                break;
        }
    }

}