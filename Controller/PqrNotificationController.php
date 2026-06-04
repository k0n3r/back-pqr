<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Controller;

use App\Bundles\pqr\Repository\PqrNotificationRepository;
use App\Bundles\pqr\Service\PqrNotificationService;
use App\Exception\ValidationFailedException;
use App\Service\JsonResponseService;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

#[Route('/notification', name: 'notification_')]
class PqrNotificationController extends AbstractController
{
    #[Route('', name: 'store', methods: ['POST'])]
    public function store(
        Request $request,
        JsonResponseService $json,
        Connection $connection,
        PqrNotificationService $service,
    ): Response {
        try {
            $connection->beginTransaction();

            $entity = $service->create([
                'fk_funcionario' => $request->request->get('id'),
            ]);

            $connection->commit();

            return $json->success($service->toArray($entity));
        } catch (Throwable $th) {
            $connection->rollBack();
            return $json->exception($th);
        }
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(
        int $id,
        Request $request,
        JsonResponseService $json,
        Connection $connection,
        PqrNotificationService $service,
        PqrNotificationRepository $repository,
        TranslatorInterface $translator,
    ): Response {
        try {
            $connection->beginTransaction();

            $entity = $repository->find($id);
            if (!$entity) {
                throw new ValidationFailedException($translator->trans('notificacion_no_encontrada'));
            }

            $service->update($entity, $request->request->all('data'));

            $connection->commit();

            return $json->success($service->toArray($entity));
        } catch (Throwable $th) {
            $connection->rollBack();
            return $json->exception($th);
        }
    }

    #[Route('/{id}', name: 'destroy', methods: ['DELETE'])]
    public function destroy(
        int $id,
        JsonResponseService $json,
        Connection $connection,
        PqrNotificationService $service,
        PqrNotificationRepository $repository,
        TranslatorInterface $translator,
    ): Response {
        try {
            $connection->beginTransaction();

            $entity = $repository->find($id);
            if (!$entity) {
                throw new ValidationFailedException($translator->trans('notificacion_no_encontrada'));
            }

            $service->delete($entity);

            $connection->commit();

            return $json->success();
        } catch (Throwable $th) {
            $connection->rollBack();
            return $json->exception($th);
        }
    }
}
