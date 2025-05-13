<?php

namespace App\Bundles\pqr\Controller;

use App\Bundles\pqr\Services\models\PqrBalancer;
use App\Bundles\pqr\Services\models\PqrForm;
use App\Exception\SaiaException;
use App\services\response\ISaiaResponse;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;


#[Route('/balancer', name: 'responseTimes_')]
class PqrBalancerController extends AbstractController
{
    #[Route('/field/{id}', name: 'groupsForField', methods: ['GET'])]
    public function groupsForField(
        int $id,
        ISaiaResponse $saiaResponse,
    ): Response {
        try {
            $record = PqrBalancer::findAllByAttributes([
                'fk_campo_opciones' => $id,
                'active'            => 1,
            ]);

            $data = [];
            $defaultOrder = 0;
            $skipOrder = false;
            foreach ($record as $PqrBalancer) {
                $CampoOpcion = $PqrBalancer->getCampoOpcionForSysTipo();

                if (!$defaultOrder) {
                    $skipOrder = is_null($CampoOpcion->orden);
                }

                $order = $skipOrder ? $defaultOrder : (int)$CampoOpcion->orden;

                $data[$order] = [
                    'id'      => $PqrBalancer->getPK(),
                    'text'    => $CampoOpcion->valor,
                    'groupId' => (int)$PqrBalancer->fk_grupo,
                ];
                $defaultOrder++;
            }

            $saiaResponse->replaceData($data);
            $saiaResponse->setSuccess(1);
        } catch (Throwable $th) {
            $saiaResponse->setMessage($th->getMessage());
        }

        return $saiaResponse->getResponse();
    }

    #[Route('', name: 'updateGroupsBalancer', methods: ['PUT'])]
    public function updateGroupsBalancer(
        Request $Request,
        ISaiaResponse $saiaResponse,
        Connection $Connection,
    ): Response {
        $Connection->beginTransaction();
        try {
            if (!$id = $Request->get('fk_field_balancer', 0)) {
                throw new SaiaException("Falta el identificador del campo de los tiempos de respuesta");
            }

            $PqrForm = PqrForm::getInstance();
            $PqrForm->getService()->save([
                'fk_field_balancer' => $id,
            ]);

            $options = $Request->get('options');
            foreach ($options as $option) {
                $PqrBalancerService = (new PqrBalancer($option['id']))->getService();
                $PqrBalancerService->save([
                    'fk_grupo' => $option['groupId'],
                ]);
            }

            $saiaResponse->setSuccess(1);
            $Connection->commit();
        } catch (Throwable $th) {
            $Connection->rollBack();
            $saiaResponse->setMessage($th->getMessage());
        }

        return $saiaResponse->getResponse();
    }
}