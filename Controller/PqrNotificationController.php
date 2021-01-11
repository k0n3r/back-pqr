<?php

namespace App\Bundles\pqr\Controller;

use Saia\core\DatabaseConnection;
use App\services\response\ISaiaResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Bundles\pqr\Services\models\PqrNotification;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class PqrNotificationController extends AbstractController
{
    /**
     * @Route("/notification", name="store", methods={"POST"}) 
     */
    public function store(
        Request $request,
        ISaiaResponse $saiaResponse
    ): Response {

        try {
            $Connection = DatabaseConnection::getDefaultConnection();
            $Connection->beginTransaction();

            $PqrNotificationService = (new PqrNotification())->getService();
            if (!$PqrNotificationService->create([
                'fk_funcionario' => $request->get('id')
            ])) {
                throw new \Exception($PqrNotificationService->getErrorMessage(), 1);
            }

            $data = $PqrNotificationService->getModel()->getDataAttributes();

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
     * @Route("/notification/{id}", name="update", methods={"PUT"})
     */
    public function update(
        int $id,
        Request $request,
        ISaiaResponse $saiaResponse
    ): Response {

        try {
            $Connection = DatabaseConnection::getDefaultConnection();
            $Connection->beginTransaction();

            $PqrNotificationService = (new PqrNotification($id))->getService();
            if (!$PqrNotificationService->update($request->get('data'))) {
                throw new \Exception($PqrNotificationService->getErrorMessage(), 1);
            }

            $data = $PqrNotificationService->getModel()->getDataAttributes();

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
     * @Route("/notification/{id}", name="destroy", methods={"DELETE"})
     */
    public function destroy(
        int $id,
        ISaiaResponse $saiaResponse
    ): Response {

        try {
            $Connection = DatabaseConnection::getDefaultConnection();
            $Connection->beginTransaction();

            $PqrNotificationService = (new PqrNotification($id))->getService();
            if (!$PqrNotificationService->delete()) {
                throw new \Exception($PqrNotificationService->getErrorMessage(), 1);
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
