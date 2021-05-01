<?php

namespace App\Bundles\pqr\Controller;

use App\services\response\ISaiaResponse;
use Exception;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Bundles\pqr\formatos\pqr_respuesta\FtPqrRespuesta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Throwable;

/**
 * @Route("/answers/{idft}", name="FtPqrRespuesta_")
 */
class FtPqrRespuestaController extends AbstractController
{
    /**
     * @Route("/requestSurveyByEmail", name="requestSurveyByEmail", methods={"GET"})
     */
    public function requestSurvey(
        int $idft,
        ISaiaResponse $saiaResponse
    ): Response {

        try {

            $FtPqrRespuestaService = (new FtPqrRespuesta($idft))->getService();

            if (!$FtPqrRespuestaService->requestSurvey()) {
                throw new Exception(
                    $FtPqrRespuestaService->getErrorManager()->getMessage(),
                    $FtPqrRespuestaService->getErrorManager()->getCode()
                );
            }

            $saiaResponse->setSuccess(1);
        } catch (Throwable $th) {
            $saiaResponse->setMessage($th->getMessage());
        }

        return $saiaResponse->getResponse();
    }
}
