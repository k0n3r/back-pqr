<?php

namespace App\Bundles\pqr\Controller;

use App\Exception\SaiaException;
use App\services\GlobalContainer;
use App\services\response\SaiaResponse;
use Doctrine\DBAL\Connection;
use App\services\response\ISaiaResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Bundles\pqr\Services\models\PqrFormField;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Throwable;

#[Route('/formField', name: 'formField_')]
class PqrFormFieldController extends AbstractController
{
    #[Route('', name: 'store', methods: ['POST'])]
    public function store(
        Request $request,
        ISaiaResponse $saiaResponse,
        Connection $Connection
    ): Response {

        try {
            $Connection->beginTransaction();

            $PqrFormFieldService = (new PqrFormField())->getService();
            if (!$PqrFormFieldService->save($request->get('data'))) {
                throw new SaiaException(
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

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(
        int $id,
        Request $request,
        ISaiaResponse $saiaResponse,
        Connection $Connection
    ): Response {

        try {
            $Connection->beginTransaction();

            $PqrFormFieldService = (new PqrFormField($id))->getService();
            if (!$PqrFormFieldService->save($request->get('data'))) {
                throw new SaiaException(
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

    #[Route('/{id}/active', name: 'active', methods: ['PUT'])]
    public function active(
        int $id
    ): Response {

        return $this->activeInactive($id, PqrFormField::ACTIVE);
    }

    #[Route('/{id}/inactive', name: 'inactive', methods: ['PUT'])]
    public function inactive(
        int $id
    ): Response {

        return $this->activeInactive($id, PqrFormField::INACTIVE);
    }

    private function activeInactive(
        int $id,
        int $status
    ): Response {

        $saiaResponse = new SaiaResponse();
        $Connection = GlobalContainer::getConnection();

        try {
            $Connection->beginTransaction();

            $PqrFormFieldService = (new PqrFormField($id))->getService();
            if (!$PqrFormFieldService->updateActive($status)) {
                throw new SaiaException(
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

    #[Route('/{id}', name: 'destroy', methods: ['DELETE'])]
    public function destroy(
        int $id,
        ISaiaResponse $saiaResponse,
        Connection $Connection
    ): Response {

        try {
            $Connection->beginTransaction();

            $PqrFormFieldService = (new PqrFormField($id))->getService();
            if (!$PqrFormFieldService->delete()) {
                throw new SaiaException(
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
