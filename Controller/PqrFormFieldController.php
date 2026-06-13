<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Controller;

use App\Bundles\pqr\Entity\PqrFormField;
use App\Bundles\pqr\Service\PqrFormFieldServiceFactory;
use App\Service\JsonResponseService;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

#[Route('/formField', name: 'formField_')]
class PqrFormFieldController extends AbstractController
{
    #[Route('', name: 'store', methods: ['POST'])]
    public function store(
        Request $request,
        jsonResponseService $json,
        Connection $Connection,
        PqrFormFieldServiceFactory $factory,
    ): Response {
        try {
            $Connection->beginTransaction();

            $PqrFormFieldService = $factory->create();
            $PqrFormFieldService->save($request->request->all('data'));

            $data = $PqrFormFieldService->getModel()->getDataAttributes();

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
        PqrFormFieldServiceFactory $factory,
    ): Response {
        try {
            $Connection->beginTransaction();

            $PqrFormFieldService = $factory->create($id);
            $PqrFormFieldService->save($request->request->all('data'));

            $data = $PqrFormFieldService->getModel()->getDataAttributes();

            $Connection->commit();

            return $json->success($data);
        } catch (Throwable $th) {
            $Connection->rollBack();

            return $json->exception($th);
        }
    }

    #[Route('/{id}/active', name: 'active', methods: ['PUT'])]
    public function active(
        int $id,
        Connection $connection,
        jsonResponseService $json,
        PqrFormFieldServiceFactory $factory,
    ): Response {
        return $this->activeInactive($id, PqrFormField::ACTIVE, $connection, $json, $factory);
    }

    #[Route('/{id}/inactive', name: 'inactive', methods: ['PUT'])]
    public function inactive(
        int $id,
        Connection $connection,
        jsonResponseService $json,
        PqrFormFieldServiceFactory $factory,
    ): Response {
        return $this->activeInactive($id, PqrFormField::INACTIVE, $connection, $json, $factory);
    }

    private function activeInactive(
        int $id,
        int $status,
        Connection $Connection,
        JsonResponseService $json,
        PqrFormFieldServiceFactory $factory,
    ): Response {
        try {
            $Connection->beginTransaction();

            $PqrFormFieldService = $factory->create($id);
            $PqrFormFieldService->updateActive($status);

            $data = $PqrFormFieldService->getModel()->getDataAttributes();

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
        PqrFormFieldServiceFactory $factory,
    ): Response {
        try {
            $Connection->beginTransaction();

            $PqrFormFieldService = $factory->create($id);
            $PqrFormFieldService->delete();

            $Connection->commit();

            return $json->success();
        } catch (Throwable $th) {
            $Connection->rollBack();

            return $json->exception($th);
        }
    }
}
