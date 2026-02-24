<?php

namespace App\Bundles\pqr\Controller;

use App\Bundles\pqr\Services\models\PqrNotyMessage;
use App\Bundles\pqr\Services\PqrNotyMessageService;
use App\Exception\ValidationFailedException;
use App\Service\JsonResponseService;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

#[Route('/notyMessage', name: 'notyMessage_')]
class PqrNotyMessageController extends AbstractController
{
    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(
        int $id,
        Request $request,
        jsonResponseService $json,
        Connection $Connection,
    ): Response {
        try {
            $Connection->beginTransaction();

            $PqrNotyMessageService = (new PqrNotyMessage($id))->getService();
            if (!$PqrNotyMessageService->save($request->request->get('data'))) {
                throw new ValidationFailedException(
                    $PqrNotyMessageService->getErrorManager()->getMessage(),
                );
            }

            $data = PqrNotyMessageService::getDataPqrNotyMessages();
            $Connection->commit();

            return $json->success($data);
        } catch (Throwable $th) {
            $Connection->rollBack();

            return $json->exception($th);
        }
    }
}
