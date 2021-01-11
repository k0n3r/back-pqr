<?php

namespace App\Bundles\pqr\Controller;

use Saia\core\DatabaseConnection;
use App\Bundles\pqr\Services\PqrService;
use App\services\response\ISaiaResponse;
use App\Bundles\pqr\Services\models\PqrForm;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class PqrFormController extends AbstractController
{

    /**
     * @Route("/form/publish", name="publish", methods={"GET"})
     */
    public function publish(
        ISaiaResponse $saiaResponse
    ): Response {

        try {
            $Connection = DatabaseConnection::getDefaultConnection();
            $Connection->beginTransaction();

            $PqrFormService = (PqrForm::getPqrFormActive())->getService();
            if (!$PqrFormService->publish()) {
                throw new \Exception($PqrFormService->getErrorMessage(), 1);
            }

            $data = [
                'pqrForm' => $PqrFormService->getDataPqrForm(),
                'pqrFormFields' => $PqrFormService->getDataPqrFormFields(),
            ];

            $saiaResponse->replaceData($data);
            $saiaResponse->setSuccess(1);
            $Connection->commit();
        } catch (\Throwable $th) {
            $Connection->rollBack();
            $saiaResponse->setMessage($th->getMessage());
        }

        return $saiaResponse->getResponse();
    }

    /**
     * @Route("/form/setting", name="getSetting", methods={"GET"})
     */
    public function getSetting(
        ISaiaResponse $saiaResponse
    ): Response {

        try {
            $data = (PqrForm::getPqrFormActive())->getService()
                ->getSetting();

            $saiaResponse->replaceData($data);
            $saiaResponse->setSuccess(1);
        } catch (\Throwable $th) {
            $saiaResponse->setMessage($th->getMessage());
        }
        return $saiaResponse->getResponse();
    }

    /**
     * @Route("/form/updateSetting", name="updateSetting", methods={"PUT"})
     */
    public function updateSetting(
        Request $request,
        ISaiaResponse $saiaResponse
    ): Response {

        try {
            $Connection = DatabaseConnection::getDefaultConnection();
            $Connection->beginTransaction();

            $PqrFormService = (PqrForm::getPqrFormActive())->getService();
            if (!$PqrFormService->updateSetting($request->get('data'))) {
                throw new \Exception($PqrFormService->getErrorMessage(), 1);
            }

            $data = [
                'pqrForm' => $PqrFormService->getDataPqrForm(),
                'pqrFormFields' => $PqrFormService->getDataPqrFormFields(),
            ];

            $saiaResponse->replaceData($data);
            $saiaResponse->setSuccess(1);
            $Connection->commit();
        } catch (\Throwable $th) {
            $Connection->rollBack();
            $saiaResponse->setMessage($th->getMessage());
        }

        return $saiaResponse->getResponse();
    }

    /**
     * @Route("/form/responseSetting", name="getResponseSetting", methods={"GET"}) 
     */
    public function getResponseSetting(
        ISaiaResponse $saiaResponse
    ): Response {

        try {
            $data = (PqrForm::getPqrFormActive())
                ->getResponseConfiguration(true) ?? [];

            $saiaResponse->replaceData($data);
            $saiaResponse->setSuccess(1);
        } catch (\Throwable $th) {
            $saiaResponse->setMessage($th->getMessage());
        }

        return $saiaResponse->getResponse();
    }

    /**
     * @Route("/form/updateResponseSetting", name="updateResponseSetting", methods={"PUT"})
     */
    public function updateResponseSetting(
        Request $request,
        ISaiaResponse $saiaResponse
    ): Response {

        try {
            $Connection = DatabaseConnection::getDefaultConnection();
            $Connection->beginTransaction();

            $PqrFormService = (PqrForm::getPqrFormActive())->getService();
            if (!$PqrFormService->updateResponseSetting($request->get('data'))) {
                throw new \Exception($PqrFormService->getErrorMessage(), 1);
            }

            $saiaResponse->setSuccess(1);
            $Connection->commit();
        } catch (\Throwable $th) {
            $Connection->rollBack();
            $saiaResponse->setMessage($th->getMessage());
        }

        return $saiaResponse->getResponse();
    }

    /**
     * @Route("/form/updatePqrTypes", name="updatePqrTypes", methods={"PUT"})
     */
    public function updatePqrTypes(
        Request $request,
        ISaiaResponse $saiaResponse
    ): Response {

        try {
            $Connection = DatabaseConnection::getDefaultConnection();
            $Connection->beginTransaction();

            $PqrFormService = (PqrForm::getPqrFormActive())->getService();
            if (!$PqrFormService->updatePqrTypes($request->get('data'))) {
                throw new \Exception($PqrFormService->getErrorMessage(), 1);
            }

            $saiaResponse->setSuccess(1);
            $Connection->commit();
        } catch (\Throwable $th) {
            $Connection->rollBack();
            $saiaResponse->setMessage($th->getMessage());
        }

        return $saiaResponse->getResponse();
    }
}
