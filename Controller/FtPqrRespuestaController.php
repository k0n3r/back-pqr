<?php

namespace App\Bundles\pqr\Controller;

use App\services\response\ISaiaResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Bundles\pqr\formatos\pqr_respuesta\FtPqrRespuesta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Route("/answers/{idft}", name="FtPqrRespuesta_")
 */
class FtPqrRespuestaController extends AbstractController
{
    /**
     * @Route("/requestSurveyByEmail", name="requestSurveyByEmail", methods={"GET"})
     */
    public function requestSurveyByEmail(
        int $idft,
        ISaiaResponse $saiaResponse
    ): Response {

        try {

            $FtPqrRespuesta = new FtPqrRespuesta($idft);

            if (!$FtPqrRespuesta->requestSurvey()) {
                throw new \Exception("No fue posible solicitar la calificación", 1);
            }

            $saiaResponse->setSuccess(1);
        } catch (\Throwable $th) {
            $saiaResponse->setMessage($th->getMessage());
        }

        return $saiaResponse->getResponse();
    }
}
