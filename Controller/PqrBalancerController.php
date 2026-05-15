<?php

namespace App\Bundles\pqr\Controller;

use App\Bundles\pqr\Entity\PqrBalancer as PqrBalancerEntity;
use App\Bundles\pqr\Repository\PqrBalancerRepository;
use App\Bundles\pqr\Service\PqrFormService;
use App\Exception\MissingParameterException;
use App\Service\JsonResponseService;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Saia\models\formatos\CampoOpciones;
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
        PqrBalancerRepository $pqrBalancerRepository,
    ): Response {
        try {
            $records = $pqrBalancerRepository->findBy([
                'fkCampoOpciones' => $id,
                'active'          => true,
            ]);

            $data = [];
            $defaultOrder = 0;
            $skipOrder = false;
            foreach ($records as $pqrBalancer) {
                $CampoOpcion = new CampoOpciones($pqrBalancer->getFkSysTipo());

                if (!$defaultOrder) {
                    $skipOrder = is_null($CampoOpcion->orden);
                }

                $order = $skipOrder ? $defaultOrder : (int)$CampoOpcion->orden;

                $data[$order] = [
                    'id'      => $pqrBalancer->getId(),
                    'text'    => $CampoOpcion->valor,
                    'groupId' => $pqrBalancer->getFkGrupo(),
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
        PqrFormService $pqrFormService,
        PqrBalancerRepository $pqrBalancerRepository,
        EntityManagerInterface $em,
    ): Response {
        $Connection->beginTransaction();
        try {
            if (!$id = $Request->request->get('fk_field_balancer', 0)) {
                $trans = $translator->trans("falta_identificador_tiempo_respuesta");
                throw new MissingParameterException($trans);
            }

            $pqrFormService->save([
                'fk_field_balancer' => $id,
            ]);

            foreach ($Request->request->all('options') as $option) {
                $entity = $pqrBalancerRepository->find($option['id']);
                if ($entity instanceof PqrBalancerEntity) {
                    $entity->setFkGrupo((int)$option['groupId']);
                }
            }
            $em->flush();

            $Connection->commit();

            return $json->success();
        } catch (Throwable $th) {
            $Connection->rollBack();

            return $json->exception($th);
        }
    }
}
