<?php

namespace App\Bundles\pqr\Controller;

use App\Bundles\pqr\Services\models\PqrForm;
use App\Bundles\pqr\Services\PqrService;
use App\Service\JsonResponseService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

#[Route('/structure', name: 'structure_')]
class StructureController extends AbstractController
{
    #[Route('/dataViewIndex', name: 'dataViewIndex', methods: ['GET'])]
    public function getDataViewIndex(
        jsonResponseService $json,
    ): Response {
        try {
            $PqrFormService = PqrForm::getInstance()->getService();

            $data = [
                'pqrForm'       => $PqrFormService->getDataPqrForm(),
                'pqrFormFields' => $PqrFormService->getDataPqrFormFields(),
                'pqrHtmlFields' => PqrService::getDataHtmlFields(),
            ];

            return $json->success($data);
        } catch (Throwable $th) {
            return $json->exception($th);
        }
    }

    #[Route('/dataModalViewEditType', name: 'getDataEditType', methods: ['GET'])]
    public function getDataEditType(
        jsonResponseService $json,
    ): Response {
        try {
            $data = (new PqrService())->getDataForEditTypes();

            return $json->success($data);
        } catch (Throwable $th) {
            return $json->exception($th);
        }
    }
}
