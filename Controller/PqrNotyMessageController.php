<?php

namespace App\Bundles\pqr\Controller;

use App\Bundles\pqr\Service\PqrNotyMessageService;
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
        PqrNotyMessageService $pqrNotyMessageService,
    ): Response {
        try {
            $Connection->beginTransaction();

            $pqrNotyMessageService->update($id, $request->request->all('data'));

            $data = $pqrNotyMessageService->getDataPqrNotyMessages();
            $Connection->commit();

            return $json->success($data);
        } catch (Throwable $th) {
            $Connection->rollBack();

            return $json->exception($th);
        }
    }
}
