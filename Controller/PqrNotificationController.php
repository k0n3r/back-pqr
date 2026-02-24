<?php

namespace App\Bundles\pqr\Controller;

use App\Bundles\pqr\Services\models\PqrNotification;
use App\Exception\ValidationFailedException;
use App\Service\JsonResponseService;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

#[Route('/notification', name: 'notification_')]
class PqrNotificationController extends AbstractController
{
    #[Route('', name: 'store', methods: ['POST'])]
    public function store(
        Request $request,
        jsonResponseService $json,
        Connection $Connection,
    ): Response {
        try {
            $Connection->beginTransaction();

            $PqrNotificationService = (new PqrNotification())->getService();
            if (!$PqrNotificationService->create([
                'fk_funcionario' => $request->request->get('id'),
            ])) {
                throw new ValidationFailedException(
                    $PqrNotificationService->getErrorManager()->getMessage(),
                );
            }

            $data = $PqrNotificationService->getModel()->getDataAttributes();

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

            $PqrNotificationService = (new PqrNotification($id))->getService();
            if (!$PqrNotificationService->update($request->request->get('data'))) {
                throw new ValidationFailedException(
                    $PqrNotificationService->getErrorManager()->getMessage(),
                );
            }

            $data = $PqrNotificationService->getModel()->getDataAttributes();

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

            $PqrNotificationService = (new PqrNotification($id))->getService();
            if (!$PqrNotificationService->delete()) {
                throw new ValidationFailedException(
                    $PqrNotificationService->getErrorManager()->getMessage(),
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
