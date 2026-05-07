<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Controller;

use App\Bundles\pqr\Services\models\PqrForm;
use App\Bundles\pqr\Services\PqrService;
use App\Service\JsonResponseService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

#[Route('/structure', name: 'structure_')]
class StructureController extends AbstractController
{
    #[Route('/dataViewIndex', name: 'dataViewIndex', methods: ['GET'])]
    public function getDataViewIndex(
        JsonResponseService $json,
        PqrService $pqrService,
    ): Response {
        try {
            $PqrFormService = PqrForm::getInstance()->getService();

            $data = [
                'pqrForm'       => $PqrFormService->getDataPqrForm(),
                'pqrFormFields' => $PqrFormService->getDataPqrFormFields(),
                'pqrHtmlFields' => $pqrService->getDataHtmlFields(),
            ];

            return $json->success($data);
        } catch (Throwable $th) {
            return $json->exception($th);
        }
    }

    #[Route('/dataModalViewEditType', name: 'getDataEditType', methods: ['GET'])]
    public function getDataEditType(
        JsonResponseService $json,
        PqrService $pqrService,
    ): Response {
        try {
            $data = $pqrService->getDataForEditTypes();

            return $json->success($data);
        } catch (Throwable $th) {
            return $json->exception($th);
        }
    }
}
