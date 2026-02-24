<?php

namespace App\Bundles\pqr\Controller;

use App\Bundles\pqr\Services\models\PqrFormField;
use App\Exception\ValidationFailedException;
use App\Service\JsonResponseService;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

#[Route('/formField', name: 'formField_')]
class PqrFormFieldController extends AbstractController
{
    #[Route('', name: 'store', methods: ['POST'])]
    public function store(
        Request $request,
        jsonResponseService $json,
        Connection $Connection,
    ): Response {
        try {
            $Connection->beginTransaction();

            $PqrFormFieldService = (new PqrFormField())->getService();
            if (!$PqrFormFieldService->save($request->request->get('data'))) {
                throw new ValidationFailedException(
                    $PqrFormFieldService->getErrorManager()->getMessage(),
                );
            }

            $data = $PqrFormFieldService->getModel()->getDataAttributes();

            $Connection->commit();

            return $json->success($data);
        } catch (Throwable $th) {
            $Connection->rollBack();

            return $json->exception($th);
        }
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(
        int $id,
        Request $request,
        jsonResponseService $json,
        Connection $Connection,
    ): Response {
        try {
            $Connection->beginTransaction();

            $PqrFormFieldService = (new PqrFormField($id))->getService();
            if (!$PqrFormFieldService->save($request->request->all('data'))) {
                throw new ValidationFailedException(
                    $PqrFormFieldService->getErrorManager()->getMessage(),
                );
            }

            $data = $PqrFormFieldService->getModel()->getDataAttributes();

            $Connection->commit();

            return $json->success($data);
        } catch (Throwable $th) {
            $Connection->rollBack();

            return $json->exception($th);
        }
    }

    #[Route('/{id}/active', name: 'active', methods: ['PUT'])]
    public function active(
        int $id,
        Connection $connection,
        jsonResponseService $json,
    ): Response {
        return $this->activeInactive($id, PqrFormField::ACTIVE, $connection, $json);
    }

    #[Route('/{id}/inactive', name: 'inactive', methods: ['PUT'])]
    public function inactive(
        int $id,
        Connection $connection,
        jsonResponseService $json,
    ): Response {
        return $this->activeInactive($id, PqrFormField::INACTIVE, $connection, $json);
    }

    private function activeInactive(
        int $id,
        int $status,
        Connection $Connection,
        JsonResponseService $json,
    ): Response {
        try {
            $Connection->beginTransaction();

            $PqrFormFieldService = (new PqrFormField($id))->getService();
            if (!$PqrFormFieldService->updateActive($status)) {
                throw new ValidationFailedException(
                    $PqrFormFieldService->getErrorManager()->getMessage(),
                );
            }

            $data = $PqrFormFieldService->getModel()->getDataAttributes();

            $Connection->commit();

            return $json->success($data);
        } catch (Throwable $th) {
            $Connection->rollBack();

            return $json->exception($th);
        }
    }

    #[Route('/{id}', name: 'destroy', methods: ['DELETE'])]
    public function destroy(
        int $id,
        jsonResponseService $json,
        Connection $Connection,
    ): Response {
        try {
            $Connection->beginTransaction();

            $PqrFormFieldService = (new PqrFormField($id))->getService();
            if (!$PqrFormFieldService->delete()) {
                throw new ValidationFailedException(
                    $PqrFormFieldService->getErrorManager()->getMessage(),
                );
            }

            $Connection->commit();

            return $json->success();
        } catch (Throwable $th) {
            $Connection->rollBack();

            return $json->exception($th);
        }
    }
}
