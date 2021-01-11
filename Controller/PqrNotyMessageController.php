<?php

namespace App\Bundles\pqr\Controller;

use Saia\core\DatabaseConnection;
use App\services\response\ISaiaResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Bundles\pqr\Services\models\PqrNotyMessage;
use App\Bundles\pqr\Services\PqrNotyMessageService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class PqrNotyMessageController extends AbstractController
{

    /**
     * @Route("/notyMessage/{id}", name="update", methods={"PUT"})
     */
    public function update(
        int $id,
        Request $request,
        ISaiaResponse $saiaResponse
    ): Response {

        try {
            $Connection = DatabaseConnection::getDefaultConnection();
            $Connection->beginTransaction();

            $PqrNotyMessageService = (new PqrNotyMessage($id))->getService();
            if (!$PqrNotyMessageService->update($request->get('data'))) {
                throw new \Exception($PqrNotyMessageService->getErrorMessage(), 1);
            }

            $data = PqrNotyMessageService::getDataPqrNotyMessages();
            $saiaResponse->replaceData($data);
            $saiaResponse->setSuccess(1);

            $Connection->commit();
        } catch (\Throwable $th) {
            $Connection->rollBack();
            $saiaResponse->setMessage($th->getMessage());
        }

        return $saiaResponse->getResponse();
    }
}
