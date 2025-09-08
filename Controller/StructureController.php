<?php

namespace App\Bundles\pqr\Controller;

use App\Bundles\pqr\Services\PqrService;
use App\Helper\Exception\ExceptionHelper;
use App\services\response\ISaiaResponse;
use App\Bundles\pqr\Services\models\PqrForm;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Throwable;

#[Route('/structure', name: 'structure_')]
class StructureController extends AbstractController
{
    use ExceptionHelper;

    #[Route('/dataViewIndex', name: 'dataViewIndex', methods: ['GET'])]
    public function getDataViewIndex(
        ISaiaResponse $saiaResponse,
    ): Response {
        try {
            $PqrFormService = PqrForm::getInstance()->getService();

            $data = [
                'pqrForm'       => $PqrFormService->getDataPqrForm(),
                'pqrFormFields' => $PqrFormService->getDataPqrFormFields(),
                'pqrHtmlFields' => PqrService::getDataHtmlFields(),
            ];

            $saiaResponse->replaceData($data);
        } catch (Throwable $th) {
            $saiaResponse->setResponseStatus($this->getExceptionStatusCode($th));
            $saiaResponse->setMessage($th->getMessage());
            $saiaResponse->deleteProperty('data');
        }
        $saiaResponse->deleteProperty('success');

        return $saiaResponse->getResponse();
    }

    #[Route('/dataModalViewEditType', name: 'getDataEditType', methods: ['GET'])]
    public function getDataEditType(
        ISaiaResponse $saiaResponse,
    ): Response {
        try {
            $data = (new PqrService())->getDataForEditTypes();

            $saiaResponse->replaceData($data);
            $saiaResponse->setSuccess(1);
        } catch (Throwable $th) {
            $saiaResponse->setMessage($th->getMessage());
        }

        return $saiaResponse->getResponse();
    }
}
