<?php

namespace App\Bundles\pqr\Controller;

use Doctrine\DBAL\Connection;
use Exception;
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
     */
    public function getTextFields(
        ISaiaResponse $saiaResponse
    ): Response {

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
     */
    public function publish(
        ISaiaResponse $saiaResponse,
        Connection $Connection
    ): Response {

        try {
            $Connection->beginTransaction();

            $PqrFormService = (PqrForm::getInstance())->getService();
            if (!$PqrFormService->publish()) {
                throw new Exception(
                    $PqrFormService->getErrorManager()->getMessage(),
                    $PqrFormService->getErrorManager()->getCode(),
                );
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
     */
    public function getSetting(
        ISaiaResponse $saiaResponse
    ): Response {

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
     */
    public function getResponseSetting(
        ISaiaResponse $saiaResponse
    ): Response {

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
     */
    public function sortFields(
        Request $request,
        ISaiaResponse $saiaResponse,
        Connection $Connection
    ): Response {

        try {
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
     */
    public function updateSetting(
        Request $request,
        ISaiaResponse $saiaResponse,
        Connection $Connection
    ): Response {

        try {
            $Connection->beginTransaction();

            $PqrFormService = (PqrForm::getInstance())->getService();
            if (!$PqrFormService->updateSetting($request->get('data'))) {
                throw new Exception(
                    $PqrFormService->getErrorManager()->getMessage(),
                    $PqrFormService->getErrorManager()->getCode(),
                );
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
     */
    public function updateResponseSetting(
        Request $request,
        ISaiaResponse $saiaResponse,
        Connection $Connection
    ): Response {

        try {
            $Connection->beginTransaction();

            $PqrFormService = (PqrForm::getInstance())->getService();
            if (!$PqrFormService->updateResponseSetting($request->get('data'))) {
                throw new Exception(
                    $PqrFormService->getErrorManager()->getMessage(),
                    $PqrFormService->getErrorManager()->getCode(),
                );
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
     */
    public function updateShowReport(
        Request $request,
        ISaiaResponse $saiaResponse,
        Connection $Connection
    ): Response {

        try {
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
            $active = $PqrFormField->isActive();
        } catch (Throwable $th) {
            $active = false;
        }

        return new JsonResponse([
            'isActive' => (int)$active
        ]);

    }
}
