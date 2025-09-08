<?php

namespace App\Bundles\pqr\Controller;

use App\Exception\ValidationFailedException;
use App\Helper\Exception\ExceptionHelper;
use Doctrine\DBAL\Connection;
use App\services\response\ISaiaResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Bundles\pqr\Services\models\PqrNotification;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Throwable;

#[Route('/notification', name: 'notification_')]
class PqrNotificationController extends AbstractController
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

            $PqrNotificationService = (new PqrNotification())->getService();
            if (!$PqrNotificationService->create([
                'fk_funcionario' => $request->get('id'),
            ])) {
                throw new ValidationFailedException(
                    $PqrNotificationService->getErrorManager()->getMessage(),
                );
            }

            $data = $PqrNotificationService->getModel()->getDataAttributes();

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

            $PqrNotificationService = (new PqrNotification($id))->getService();
            if (!$PqrNotificationService->update($request->get('data'))) {
                throw new ValidationFailedException(
                    $PqrNotificationService->getErrorManager()->getMessage(),
                );
            }

            $data = $PqrNotificationService->getModel()->getDataAttributes();

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

            $PqrNotificationService = (new PqrNotification($id))->getService();
            if (!$PqrNotificationService->delete()) {
                throw new ValidationFailedException(
                    $PqrNotificationService->getErrorManager()->getMessage(),
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
