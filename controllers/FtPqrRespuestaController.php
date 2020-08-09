<?php

namespace Saia\Pqr\controllers;

use Saia\Pqr\formatos\pqr\FtPqr;
use Saia\Pqr\formatos\pqr_respuesta\FtPqrRespuesta;

class FtPqrRespuestaController extends Controller
{

    /**
     * Solicita la encuesta
     *
     * @return object
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     * 
     * @throws Exception
     */
    public function requestSurveyByEmail(): object
    {

        $Response = (object) [
            'success' => 1,
        ];

        if ($id = $this->request['idft']) {
            $FtPqrRespuesta = new FtPqrRespuesta($id);
            if (!$FtPqrRespuesta->requestSurvey()) {
                throw new \Exception("No fue posible solicitar la calificación", 200);
            }
        }

        return $Response;
    }

    /**
     * Obtiene los campos a cargar
     *
     * @return object
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function loadField(): object
    {
        $data = [
            'destino' => 0
        ];

        if ($id = $this->request['idft']) {
            $FtPqr = new FtPqr($id);
            if ($Tercero = $FtPqr->Tercero) {
                $data['destino'] = [
                    'id' => $Tercero->getPK(),
                    'text' => "{$Tercero->identificacion} - {$Tercero->nombre}"
                ];
            };

            $data['asunto'] = "Respondiendo a la {$FtPqr->getFormat()->etiqueta} No {$FtPqr->Documento->numero}";
        }

        return (object) [
            'success' => 1,
            'data' => $data
        ];
    }
}
