<?php

namespace App\Bundles\pqr\Controller;

use App\Bundles\pqr\helpers\UtilitiesPqr;
use App\Exception\ValidationFailedException;
use App\Service\JsonResponseService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

#[Route('/answers/{idft}', name: 'FtPqrRespuesta_')]
class FtPqrRespuestaController extends AbstractController
{
    #[Route('/requestSurveyByEmail', name: 'requestSurveyByEmail', methods: ['GET'])]
    public function requestSurvey(
        int $idft,
        JsonResponseService $json,
    ): Response {
        try {
            $FtPqrRespuestaService = UtilitiesPqr::getInstanceForFtIdPqrRespuesta($idft)->getService();
            if (!$FtPqrRespuestaService->requestSurvey()) {
                throw new ValidationFailedException(
                    $FtPqrRespuestaService->getErrorManager()->getMessage(),
                );
            }

            return $json->success();
        } catch (Throwable $th) {
            return $json->exception($th);
        }
    }
}
