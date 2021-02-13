<?php

namespace App\Bundles\pqr\Controller;

use Exception;
use Saia\core\DatabaseConnection;
use App\Bundles\pqr\Services\PqrService;
use App\services\response\ISaiaResponse;
use App\Bundles\pqr\Services\models\PqrForm;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Bundles\pqr\Services\models\PqrFormField;
use App\Bundles\pqr\Services\PqrFormFieldService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Throwable;

/**
 * @Route("/form", name="form_")
 */
class PqrFormController extends AbstractController
{

    /**
     * @Route("/textFields", name="getTextFields", methods={"GET"})
     * @param ISaiaResponse $saiaResponse
     * @return Response
     */
    public function getTextFields(
        ISaiaResponse $saiaResponse
    ): Response
    {

        try {
            $saiaResponse->replaceData(PqrService::getTextFields());
            $saiaResponse->setSuccess(1);
        } catch (Throwable $th) {
            $saiaResponse->setMessage($th->getMessage());
        }

        return $saiaResponse->getResponse();
    }

    /**
     * @Route("/publish", name="publish", methods={"GET"})
     * @param ISaiaResponse $saiaResponse
     * @return Response
     * @throws Exception
     */
    public function publish(
        ISaiaResponse $saiaResponse
    ): Response
    {

        try {
            $Connection = DatabaseConnection::getDefaultConnection();
            $Connection->beginTransaction();

            $PqrFormService = (PqrForm::getInstance())->getService();
            if (!$PqrFormService->publish()) {
                throw new Exception($PqrFormService->getErrorMessage(), 1);
            }

            $data = [
                'pqrForm' => $PqrFormService->getDataPqrForm(),
                'pqrFormFields' => $PqrFormService->getDataPqrFormFields(),
            ];

            $saiaResponse->replaceData($data);
            $saiaResponse->setSuccess(1);
            $Connection->commit();
        } catch (Throwable $th) {
            $Connection->rollBack();
            $saiaResponse->setMessage($th->getMessage());
        }

        return $saiaResponse->getResponse();
    }

    /**
     * @Route("/setting", name="getSetting", methods={"GET"})
     * @param ISaiaResponse $saiaResponse
     * @return Response
     */
    public function getSetting(
        ISaiaResponse $saiaResponse
    ): Response
    {

        try {
            $data = (PqrForm::getInstance())->getService()
                ->getSetting();

            $saiaResponse->replaceData($data);
            $saiaResponse->setSuccess(1);
        } catch (Throwable $th) {
            $saiaResponse->setMessage($th->getMessage());
        }
        return $saiaResponse->getResponse();
    }

    /**
     * @Route("/responseSetting", name="getResponseSetting", methods={"GET"})
     * @param ISaiaResponse $saiaResponse
     * @return Response
     */
    public function getResponseSetting(
        ISaiaResponse $saiaResponse
    ): Response
    {

        try {
            $data = (PqrForm::getInstance())
                    ->getResponseConfiguration(true) ?? [];

            $saiaResponse->replaceData($data);
            $saiaResponse->setSuccess(1);
        } catch (Throwable $th) {
            $saiaResponse->setMessage($th->getMessage());
        }

        return $saiaResponse->getResponse();
    }

    /**
     * @Route("/sortFields", name="sortFields", methods={"PUT"})
     * @param Request       $request
     * @param ISaiaResponse $saiaResponse
     * @return Response
     * @throws Exception
     */
    public function sortFields(
        Request $request,
        ISaiaResponse $saiaResponse
    ): Response
    {

        try {
            $Connection = DatabaseConnection::getDefaultConnection();
            $Connection->beginTransaction();

            foreach ($request->get('fieldOrder') as $record) {
                $PqrFormFieldService = (new PqrFormField($record['id']))->getService();
                $status = $PqrFormFieldService->update([
                    'orden' => $record['order'] + PqrFormFieldService::INITIAL_ORDER
                ]);

                if (!$status) {
                    throw new Exception("No fue posible actualizar el orden", 1);
                }
            }

            $saiaResponse->setSuccess(1);
            $Connection->commit();
        } catch (Throwable $th) {
            $Connection->rollBack();
            $saiaResponse->setMessage($th->getMessage());
        }

        return $saiaResponse->getResponse();
    }

    /**
     * @Route("/updateSetting", name="updateSetting", methods={"PUT"})
     * @param Request       $request
     * @param ISaiaResponse $saiaResponse
     * @return Response
     * @throws Exception
     */
    public function updateSetting(
        Request $request,
        ISaiaResponse $saiaResponse
    ): Response
    {

        try {
            $Connection = DatabaseConnection::getDefaultConnection();
            $Connection->beginTransaction();

            $PqrFormService = (PqrForm::getInstance())->getService();
            if (!$PqrFormService->updateSetting($request->get('data'))) {
                throw new Exception($PqrFormService->getErrorMessage(), 1);
            }

            $data = [
                'pqrForm' => $PqrFormService->getDataPqrForm(),
                'pqrFormFields' => $PqrFormService->getDataPqrFormFields(),
            ];

            $saiaResponse->replaceData($data);
            $saiaResponse->setSuccess(1);
            $Connection->commit();
        } catch (Throwable $th) {
            $Connection->rollBack();
            $saiaResponse->setMessage($th->getMessage());
        }

        return $saiaResponse->getResponse();
    }

    /**
     * @Route("/updateResponseSetting", name="updateResponseSetting", methods={"PUT"})
     * @param Request       $request
     * @param ISaiaResponse $saiaResponse
     * @return Response
     * @throws Exception
     */
    public function updateResponseSetting(
        Request $request,
        ISaiaResponse $saiaResponse
    ): Response
    {

        try {
            $Connection = DatabaseConnection::getDefaultConnection();
            $Connection->beginTransaction();

            $PqrFormService = (PqrForm::getInstance())->getService();
            if (!$PqrFormService->updateResponseSetting($request->get('data'))) {
                throw new Exception($PqrFormService->getErrorMessage(), 1);
            }

            $saiaResponse->setSuccess(1);
            $Connection->commit();
        } catch (Throwable $th) {
            $Connection->rollBack();
            $saiaResponse->setMessage($th->getMessage());
        }

        return $saiaResponse->getResponse();
    }

    /**
     * @Route("/updatePqrTypes", name="updatePqrTypes", methods={"PUT"})
     * @param Request       $request
     * @param ISaiaResponse $saiaResponse
     * @return Response
     * @throws Exception
     */
    public function updatePqrTypes(
        Request $request,
        ISaiaResponse $saiaResponse
    ): Response
    {

        try {
            $Connection = DatabaseConnection::getDefaultConnection();
            $Connection->beginTransaction();

            $PqrFormService = (PqrForm::getInstance())->getService();
            if (!$PqrFormService->updatePqrTypes($request->get('data'))) {
                throw new Exception($PqrFormService->getErrorMessage(), 1);
            }

            $saiaResponse->setSuccess(1);
            $Connection->commit();
        } catch (Throwable $th) {
            $Connection->rollBack();
            $saiaResponse->setMessage($th->getMessage());
        }

        return $saiaResponse->getResponse();
    }

    /**
     * @Route("/updateShowReport", name="updateShowReport", methods={"PUT"})
     * @param Request       $request
     * @param ISaiaResponse $saiaResponse
     * @return Response
     * @throws Exception
     */
    public function updateShowReport(
        Request $request,
        ISaiaResponse $saiaResponse
    ): Response
    {

        try {
            $Connection = DatabaseConnection::getDefaultConnection();
            $Connection->beginTransaction();

            $Connection->createQueryBuilder()
                ->update('pqr_form_fields')
                ->set('show_report', 0)->execute();

            if ($request->get('ids')) {
                foreach ($request->get('ids') as $id) {
                    $PqrFormFieldService = (new PqrFormField($id))->getService();
                    if (!$PqrFormFieldService->update([
                        'show_report' => 1
                    ])) {
                        throw new Exception("No fue posible actualizar", 200);
                    }
                }
            }

            $PqrFormService = (PqrForm::getInstance())->getService();
            $PqrFormService->generaReport();
            $data = $PqrFormService->getDataPqrFormFields();

            $saiaResponse->replaceData($data);
            $saiaResponse->setSuccess(1);
            $Connection->commit();
        } catch (Throwable $th) {
            $Connection->rollBack();
            $saiaResponse->setMessage($th->getMessage());
        }

        return $saiaResponse->getResponse();
    }

    /**
     * @Route("/isActiveSubtype", name="isActiveSubtype", methods={"GET"})
     */
    public function isActiveSubtype(): JsonResponse
    {
        try {
            $PqrFormField = (PqrForm::getInstance())->getRow('sys_subtipo');
            $active = $PqrFormField->active;
        } catch (Throwable $th) {
            $active = 0;
        }

        return new JsonResponse([
            'isActive' => (int)$active
        ]);

    }
}
