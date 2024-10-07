<?php

namespace App\Bundles\pqr\Controller;

use App\Exception\SaiaException;
use Doctrine\DBAL\Connection;
use App\services\response\ISaiaResponse;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Bundles\pqr\Services\models\PqrNotyMessage;
use App\Bundles\pqr\Services\PqrNotyMessageService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Throwable;

/**
 * @Route("/notyMessage", name="notyMessage_")
 */
class PqrNotyMessageController extends AbstractController
{

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

            $PqrNotyMessageService = (new PqrNotyMessage($id))->getService();
            if (!$PqrNotyMessageService->save($request->get('data'))) {
                throw new SaiaException(
                    $PqrNotyMessageService->getErrorManager()->getMessage(),
                    $PqrNotyMessageService->getErrorManager()->getCode()
                );
            }

            $data = PqrNotyMessageService::getDataPqrNotyMessages();
            $saiaResponse->replaceData($data);
            $saiaResponse->setSuccess(1);

            $Connection->commit();
        } catch (Throwable $th) {
            $Connection->rollBack();
            $saiaResponse->setMessage($th->getMessage());
        }

        return $saiaResponse->getResponse();
    }
}
