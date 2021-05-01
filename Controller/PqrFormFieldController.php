<?php

namespace App\Bundles\pqr\Controller;

use Doctrine\DBAL\Connection;
use Exception;
use App\services\response\ISaiaResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Bundles\pqr\Services\models\PqrFormField;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Throwable;

/**
 * @Route("/formField", name="formField_")
 */
class PqrFormFieldController extends AbstractController
{
    /**
     * @Route("", name="store", methods={"POST"})
     */
    public function store(
        Request $request,
        ISaiaResponse $saiaResponse,
        Connection $Connection
    ): Response {

        try {
            $Connection->beginTransaction();

            $PqrFormFieldService = (new PqrFormField())->getService();
            if (!$PqrFormFieldService->create($request->get('data'))) {
                throw new Exception(
                    $PqrFormFieldService->getErrorManager()->getMessage(),
                    $PqrFormFieldService->getErrorManager()->getCode()
                );
            }

            $data = $PqrFormFieldService->getModel()->getDataAttributes();

            $saiaResponse->setSuccess(1);
            $saiaResponse->replaceData($data);

            $Connection->commit();
        } catch (Throwable $th) {
            $Connection->rollBack();
            $saiaResponse->setMessage($th->getMessage());
        }

        return $saiaResponse->getResponse();
    }

    /**
     * @Route("/{id}", name="update", methods={"PUT"})
     */
    public function update(
        int $id,
        Request $request,
        ISaiaResponse $saiaResponse,
        Connection $Connection
    ): Response {

        try {
            $Connection->beginTransaction();

            $PqrFormFieldService = (new PqrFormField($id))->getService();
            if (!$PqrFormFieldService->update($request->get('data'))) {
                throw new Exception(
                    $PqrFormFieldService->getErrorManager()->getMessage(),
                    $PqrFormFieldService->getErrorManager()->getCode()
                );
            }

            $data = $PqrFormFieldService->getModel()->getDataAttributes();

            $saiaResponse->setSuccess(1);
            $saiaResponse->replaceData($data);

            $Connection->commit();
        } catch (Throwable $th) {
            $Connection->rollBack();
            $saiaResponse->setMessage($th->getMessage());
        }

        return $saiaResponse->getResponse();
    }

    /**
     * @Route("/{id}/active", name="active", methods={"PUT"})
     */
    public function active(
        int $id,
        ISaiaResponse $saiaResponse,
        Connection $Connection
    ): Response {

        return $this->activeInactive($id, PqrFormField::ACTIVE, $saiaResponse, $Connection);
    }

    /**
     * @Route("/{id}/inactive", name="inactive", methods={"PUT"})
     */
    public function inactive(
        int $id,
        ISaiaResponse $saiaResponse,
        Connection $Connection
    ): Response {

        return $this->activeInactive($id, PqrFormField::INACTIVE, $saiaResponse, $Connection);
    }

    private function activeInactive(
        int $id,
        int $status,
        ISaiaResponse $saiaResponse,
        Connection $Connection
    ): Response {

        try {
            $Connection->beginTransaction();

            $PqrFormFieldService = (new PqrFormField($id))->getService();
            if (!$PqrFormFieldService->updateActive($status)) {
                throw new Exception(
                    $PqrFormFieldService->getErrorManager()->getMessage(),
                    $PqrFormFieldService->getErrorManager()->getCode()
                );
            }

            $data = $PqrFormFieldService->getModel()->getDataAttributes();

            $saiaResponse->setSuccess(1);
            $saiaResponse->replaceData($data);
            $Connection->commit();
        } catch (Throwable $th) {
            $Connection->rollBack();
            $saiaResponse->setMessage($th->getMessage());
        }

        return $saiaResponse->getResponse();
    }

    /**
     * @Route("/{id}", name="destroy", methods={"DELETE"})
     */
    public function destroy(
        int $id,
        ISaiaResponse $saiaResponse,
        Connection $Connection
    ): Response {

        try {
            $Connection->beginTransaction();

            $PqrFormFieldService = (new PqrFormField($id))->getService();
            if (!$PqrFormFieldService->delete()) {
                throw new Exception(
                    $PqrFormFieldService->getErrorManager()->getMessage(),
                    $PqrFormFieldService->getErrorManager()->getCode()
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
}
