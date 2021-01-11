<?php

namespace App\Bundles\pqr\Controller;

use Saia\core\DatabaseConnection;
use App\Bundles\pqr\Services\PqrService;
use App\services\response\ISaiaResponse;
use App\Bundles\pqr\Services\models\PqrForm;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Bundles\pqr\Services\models\PqrFormField;
use App\Bundles\pqr\Services\PqrFormFieldService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class PqrFormFieldController extends AbstractController
{

    /**
     * @Route("/formField/updateOrder", name="updateOrder", methods={"PUT"})
     */
    public function updateOrder(
        Request $request,
        ISaiaResponse $saiaResponse
    ): Response {

        try {
            $Connection = DatabaseConnection::getDefaultConnection();
            $Connection->beginTransaction();

            foreach ($request->get('fieldOrder') as $record) {
                $PqrFormFieldService = (new PqrFormField($record['id']))->getService();
                $status = $PqrFormFieldService->update([
                    'orden' => $record['order'] + PqrFormFieldService::INITIAL_ORDER
                ]);

                if (!$status) {
                    throw new \Exception("No fue posible actualizar el orden", 1);
                }
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
     * @Route("/formField/updateShowReport", name="updateShowReport", methods={"PUT"})
     */
    public function updateShowReport(
        Request $request,
        ISaiaResponse $saiaResponse
    ): Response {

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
                        throw new \Exception("No fue posible actualizar", 200);
                    }
                }
            }

            $PqrFormService = (PqrForm::getPqrFormActive())->getService();
            $PqrFormService->generaReport();
            $data = $PqrFormService->getDataPqrFormFields();

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
     * @Route("/formField/{id}/active", name="active", methods={"PUT"})
     */
    public function active(
        int $id,
        ISaiaResponse $saiaResponse
    ): Response {

        return $this->activeInactive($id, PqrFormField::ACTIVE, $saiaResponse);
    }

    /**
     * @Route("/formField/{id}/inactive", name="inactive", methods={"PUT"})
     */
    public function inactive(
        int $id,
        ISaiaResponse $saiaResponse
    ): Response {

        return $this->activeInactive($id, PqrFormField::INACTIVE, $saiaResponse);
    }

    private function activeInactive(
        int $id,
        int $status,
        ISaiaResponse $saiaResponse
    ): Response {

        try {
            $Connection = DatabaseConnection::getDefaultConnection();
            $Connection->beginTransaction();

            $PqrFormFieldService = (new PqrFormField($id))->getService();
            if (!$PqrFormFieldService->updateActive($status)) {
                throw new \Exception($PqrFormFieldService->getErrorMessage(), 1);
            }

            $data = $PqrFormFieldService->getModel()->getDataAttributes();

            $saiaResponse->setSuccess(1);
            $saiaResponse->replaceData($data);
            $Connection->commit();
        } catch (\Throwable $th) {
            $Connection->rollBack();
            $saiaResponse->setMessage($th->getMessage());
        }

        return $saiaResponse->getResponse();
    }

    /**
     * @Route("/formField/textFields", name="getTextFields", methods={"GET"}) 
     */
    public function getTextFields(
        ISaiaResponse $saiaResponse
    ): Response {

        try {
            $saiaResponse->replaceData(PqrService::getTextFields());
            $saiaResponse->setSuccess(1);
        } catch (\Throwable $th) {
            $saiaResponse->setMessage($th->getMessage());
        }

        return $saiaResponse->getResponse();
    }


    /**
     * @Route("/formField", name="store", methods={"POST"}) 
     */
    public function store(
        Request $request,
        ISaiaResponse $saiaResponse
    ): Response {

        try {
            $Connection = DatabaseConnection::getDefaultConnection();
            $Connection->beginTransaction();

            $PqrFormFieldService = (new PqrFormField())->getService();
            if (!$PqrFormFieldService->create($request->get('data'))) {
                throw new \Exception($PqrFormFieldService->getErrorMessage(), 1);
            }

            $data = $PqrFormFieldService->getModel()->getDataAttributes();

            $saiaResponse->setSuccess(1);
            $saiaResponse->replaceData($data);

            $Connection->commit();
        } catch (\Throwable $th) {
            $Connection->rollBack();
            $saiaResponse->setMessage($th->getMessage());
        }

        return $saiaResponse->getResponse();
    }

    /**
     * @Route("/formField/{id}", name="update", methods={"PUT"})
     */
    public function update(
        int $id,
        Request $request,
        ISaiaResponse $saiaResponse
    ): Response {

        try {
            $Connection = DatabaseConnection::getDefaultConnection();
            $Connection->beginTransaction();

            $PqrFormFieldService = (new PqrFormField($id))->getService();
            if (!$PqrFormFieldService->update($request->get('data'))) {
                throw new \Exception($PqrFormFieldService->getErrorMessage(), 1);
            }

            $data = $PqrFormFieldService->getModel()->getDataAttributes();

            $saiaResponse->setSuccess(1);
            $saiaResponse->replaceData($data);

            $Connection->commit();
        } catch (\Throwable $th) {
            $Connection->rollBack();
            $saiaResponse->setMessage($th->getMessage());
        }

        return $saiaResponse->getResponse();
    }

    /**
     * @Route("/formField/{id}", name="destroy", methods={"DELETE"})
     */
    public function destroy(
        int $id,
        ISaiaResponse $saiaResponse
    ): Response {

        try {
            $Connection = DatabaseConnection::getDefaultConnection();
            $Connection->beginTransaction();

            $PqrFormFieldService = (new PqrFormField($id))->getService();
            if (!$PqrFormFieldService->delete()) {
                throw new \Exception($PqrFormFieldService->getErrorMessage(), 1);
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
