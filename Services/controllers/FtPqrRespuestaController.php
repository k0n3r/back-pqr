<?php

namespace App\Bundles\pqr\Services\controllers;

use App\Bundles\pqr\formatos\pqr\FtPqr;

use Saia\models\formatos\Formato;

use App\Bundles\pqr\formatos\pqr_respuesta\FtPqrRespuesta;

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
        $id = $this->request['iddocPadre'];
        if (!$id) {
            throw new \Exception("Falta el identificador de la PQR", 200);
        }

        $FtPqr = FtPqr::findByDocumentId($id);
        if ($Tercero = $FtPqr->Tercero) {
            $destino = [
                'id' => $Tercero->getPK(),
                'text' => "{$Tercero->identificacion} - {$Tercero->nombre}"
            ];
        };

        $Formato = Formato::findByAttributes([
            'nombre' => 'pqr_respuesta'
        ]);

        if ($records = $Formato->getField('tipo_distribucion')->CampoOpciones) {
            foreach ($records as $CampoOpciones) {
                if ($CampoOpciones->llave == FtPqrRespuesta::DISTRIBUCION_ENVIAR_EMAIL) {
                    $tipoDistribucion = $CampoOpciones->getPK();
                    break;
                }
            }
        }

        if ($records = $Formato->getField('despedida')->CampoOpciones) {
            foreach ($records as $CampoOpciones) {
                if ($CampoOpciones->llave == FtPqrRespuesta::ATENTAMENTE_DESPEDIDA) {
                    $despedida = $CampoOpciones->getPK();
                    break;
                }
            }
        }

        return (object) [
            'success' => 1,
            'data' => [
                'destino' => $destino ?? 0,
                'tipo_distribucion' => $tipoDistribucion ?? 0,
                'despedida' => $despedida ?? 0,
                'asunto' => "Respondiendo a la {$FtPqr->getFormat()->etiqueta} No {$FtPqr->Documento->numero}"
            ]
        ];
    }
}
