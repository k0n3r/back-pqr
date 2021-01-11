<?php

namespace App\Bundles\pqr\Controller;

use Saia\controllers\CryptController;
use App\Bundles\pqr\Services\PqrService;
use App\services\response\ISaiaResponse;
use App\Bundles\pqr\Services\models\PqrForm;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class RequestProcessorController extends AbstractController
{

    /**
     * @Route("/components/allData", name="getAllData", methods={"GET"})
     */
    public function getAllData(
        ISaiaResponse $saiaResponse
    ): Response {

        try {
            $PqrFormService = PqrForm::getPqrFormActive()->getService();

            $data = [
                'pqrForm' => $PqrFormService->getDataPqrForm(),
                'pqrFormFields' => $PqrFormService->getDataPqrFormFields(),
                'pqrHtmlFields' => PqrService::getDataHtmlFields()
            ];

            $saiaResponse->replaceData($data);
            $saiaResponse->setSuccess(1);
        } catch (\Throwable $th) {
            $saiaResponse->setMessage($th->getMessage());
        }

        return $saiaResponse->getResponse();
    }

    /**
     * @Route("/components/dataForEditTypes", name="getDataForEditTypes", methods={"GET"})
     */
    public function getDataForEditTypes(
        ISaiaResponse $saiaResponse
    ): Response {

        try {
            $data = (new PqrService())->getDataForEditTypes();

            $saiaResponse->replaceData($data);
            $saiaResponse->setSuccess(1);
        } catch (\Throwable $th) {
            $saiaResponse->setMessage($th->getMessage());
        }

        return $saiaResponse->getResponse();
    }

    /**
     * @Route("components/listForField", name="getListForField", methods={"GET"})
     */
    public function getListForField(
        Request $request
    ): JsonResponse {

        $data = (new PqrService())->getListForField($request->get('data'));

        return new JsonResponse($data);
    }

    /**
     * Desencripta la informacion
     *
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     */
    public function decrypt(): array
    {
        $data = json_decode(CryptController::decrypt($this->request['dataCrypt']), true);

        return [
            'data' => $data
        ];
    }
}
