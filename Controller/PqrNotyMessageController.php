<?php

namespace App\Bundles\pqr\Controller;

use App\Exception\ValidationFailedException;
use App\Helper\Exception\ExceptionHelper;
use Doctrine\DBAL\Connection;
use App\services\response\ISaiaResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Bundles\pqr\Services\models\PqrNotyMessage;
use App\Bundles\pqr\Services\PqrNotyMessageService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Throwable;

#[Route('/notyMessage', name: 'notyMessage_')]
class PqrNotyMessageController extends AbstractController
{
    use ExceptionHelper;

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(
        int $id,
        Request $request,
        ISaiaResponse $saiaResponse,
        Connection $Connection,
    ): Response {
        try {
            $Connection->beginTransaction();

            $PqrNotyMessageService = (new PqrNotyMessage($id))->getService();
            if (!$PqrNotyMessageService->save($request->get('data'))) {
                throw new ValidationFailedException(
                    $PqrNotyMessageService->getErrorManager()->getMessage(),
                );
            }

            $data = PqrNotyMessageService::getDataPqrNotyMessages();
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
}
