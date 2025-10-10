<?php

namespace App\Bundles\pqr\Controller;

use App\Bundles\pqr\Services\models\PqrFormField;
use App\Exception\ValidationFailedException;
use App\Helper\Exception\ExceptionHelper;
use App\services\response\ISaiaResponse;
use App\services\response\SaiaResponse;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

#[Route('/formField', name: 'formField_')]
class PqrFormFieldController extends AbstractController
{
    use ExceptionHelper;

    #[Route('', name: 'store', methods: ['POST'])]
    public function store(
        Request $request,
        ISaiaResponse $saiaResponse,
        Connection $Connection,
    ): Response {
        try {
            $Connection->beginTransaction();

            $PqrFormFieldService = (new PqrFormField())->getService();
            if (!$PqrFormFieldService->save($request->get('data'))) {
                throw new ValidationFailedException(
                    $PqrFormFieldService->getErrorManager()->getMessage(),
                );
            }

            $data = $PqrFormFieldService->getModel()->getDataAttributes();

            $saiaResponse->replaceData($data);

            $Connection->commit();
        } catch (Throwable $th) {
            $Connection->rollBack();
            $saiaResponse->setResponseStatus($this->getExceptionStatusCode($th));
            $saiaResponse->setMessage($th->getMessage());
            $saiaResponse->deleteProperty('data');
        }
        $saiaResponse->deleteProperty('success');

        return $saiaResponse->getResponse();
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(
        int $id,
        Request $request,
        ISaiaResponse $saiaResponse,
        Connection $Connection,
    ): Response {
        try {
            $Connection->beginTransaction();

            $PqrFormFieldService = (new PqrFormField($id))->getService();
            if (!$PqrFormFieldService->save($request->get('data'))) {
                throw new ValidationFailedException(
                    $PqrFormFieldService->getErrorManager()->getMessage(),
                );
            }

            $data = $PqrFormFieldService->getModel()->getDataAttributes();

            $saiaResponse->replaceData($data);
            $Connection->commit();
        } catch (Throwable $th) {
            $Connection->rollBack();
            $saiaResponse->setResponseStatus($this->getExceptionStatusCode($th));
            $saiaResponse->setMessage($th->getMessage());
            $saiaResponse->deleteProperty('data');
        }
        $saiaResponse->deleteProperty('success');

        return $saiaResponse->getResponse();
    }

    #[Route('/{id}/active', name: 'active', methods: ['PUT'])]
    public function active(
        int $id,
        Connection $connection,
    ): Response {
        return $this->activeInactive($id, PqrFormField::ACTIVE, $connection);
    }

    #[Route('/{id}/inactive', name: 'inactive', methods: ['PUT'])]
    public function inactive(
        int $id,
        Connection $connection,
    ): Response {
        return $this->activeInactive($id, PqrFormField::INACTIVE, $connection);
    }

    private function activeInactive(
        int $id,
        int $status,
        Connection $Connection,
    ): Response {
        $saiaResponse = new SaiaResponse();

        try {
            $Connection->beginTransaction();

            $PqrFormFieldService = (new PqrFormField($id))->getService();
            if (!$PqrFormFieldService->updateActive($status)) {
                throw new ValidationFailedException(
                    $PqrFormFieldService->getErrorManager()->getMessage(),
                );
            }

            $data = $PqrFormFieldService->getModel()->getDataAttributes();

            $saiaResponse->replaceData($data);
            $Connection->commit();
        } catch (Throwable $th) {
            $Connection->rollBack();
            $saiaResponse->setResponseStatus($this->getExceptionStatusCode($th));
            $saiaResponse->setMessage($th->getMessage());
            $saiaResponse->deleteProperty('data');
        }
        $saiaResponse->deleteProperty('success');

        return $saiaResponse->getResponse();
    }

    #[Route('/{id}', name: 'destroy', methods: ['DELETE'])]
    public function destroy(
        int $id,
        ISaiaResponse $saiaResponse,
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
        } catch (Throwable $th) {
            $Connection->rollBack();
            $saiaResponse->setResponseStatus($this->getExceptionStatusCode($th));
            $saiaResponse->setMessage($th->getMessage());
            $saiaResponse->deleteProperty('data');
        }
        $saiaResponse->deleteProperty('success');

        return $saiaResponse->getResponse();
    }
}
