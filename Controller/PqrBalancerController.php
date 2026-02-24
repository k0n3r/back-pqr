<?php

namespace App\Bundles\pqr\Controller;

use App\Bundles\pqr\Services\models\PqrBalancer;
use App\Bundles\pqr\Services\models\PqrForm;
use App\Exception\MissingParameterException;
use App\Service\JsonResponseService;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

#[Route('/balancer', name: 'responseTimes_')]
class PqrBalancerController extends AbstractController
{
    #[Route('/field/{id}', name: 'groupsForField', methods: ['GET'])]
    public function groupsForField(
        int $id,
        jsonResponseService $json,
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

            return $json->success($data);
        } catch (Throwable $th) {
            return $json->exception($th);
        }
    }

    #[Route('', name: 'updateGroupsBalancer', methods: ['PUT'])]
    public function updateGroupsBalancer(
        Request $Request,
        jsonResponseService $json,
        Connection $Connection,
        TranslatorInterface $translator,
    ): Response {
        $Connection->beginTransaction();
        try {
            if (!$id = $Request->request->get('fk_field_balancer', 0)) {
                $trans = $translator->trans("falta_identificador_tiempo_respuesta");
                throw new MissingParameterException($trans);
            }

            $PqrForm = PqrForm::getInstance();
            $PqrForm->getService()->save([
                'fk_field_balancer' => $id,
            ]);

            $options = $Request->request->all('options');
            foreach ($options as $option) {
                $PqrBalancerService = (new PqrBalancer($option['id']))->getService();
                $PqrBalancerService->save([
                    'fk_grupo' => $option['groupId'],
                ]);
            }

            $Connection->commit();

            return $json->success();
        } catch (Throwable $th) {
            $Connection->rollBack();

            return $json->exception($th);
        }
    }
}
