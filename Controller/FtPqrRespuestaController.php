<?php

namespace App\Bundles\pqr\Controller;

use App\Bundles\pqr\helpers\UtilitiesPqr;
use App\Exception\SaiaException;
use App\services\response\ISaiaResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Throwable;

#[Route('/answers/{idft}', name: 'FtPqrRespuesta_')]
class FtPqrRespuestaController extends AbstractController
{
    #[Route('/requestSurveyByEmail', name: 'requestSurveyByEmail', methods: ['GET'])]
    public function requestSurvey(
        int $idft,
        ISaiaResponse $saiaResponse
    ): Response {

        try {
            $FtPqrRespuestaService = UtilitiesPqr::getInstanceForFtIdPqrRespuesta($idft)->getService();
            if (!$FtPqrRespuestaService->requestSurvey()) {
                throw new SaiaException(
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
