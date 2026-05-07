<?php

namespace App\Bundles\pqr\Controller;

use App\Bundles\pqr\Repository\PqrResponseTimeRepository;
use App\Bundles\pqr\Services\PqrFormService;
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

#[Route('/responseTimes', name: 'responseTimes_')]
class PqrResponseTimeController extends AbstractController
{
    #[Route('/field/{id}', name: 'timesForField', methods: ['GET'])]
    public function timesForField(
        int $id,
        jsonResponseService $json,
        PqrResponseTimeRepository $pqrResponseTimeRepository,
    ): Response {
        try {
            $records = $pqrResponseTimeRepository->findBy([
                'fkCampoOpciones' => $id,
                'active'          => true,
            ]);

            $data = [];
            $keys = [];
            $mayor = 0;
            foreach ($records as $rt) {
                $CampoOpcion = new CampoOpciones($rt->getFkSysTipo());

                $key = (int)$CampoOpcion->orden;
                $mayor = max($key, $mayor);

                if (!in_array($key, $keys)) {
                    $keys[] = $key;
                    $orden = $key;
                } else {
                    $mayor++;
                    $orden = $mayor;
                }

                $data[$orden] = [
                    'id'   => $rt->getId(),
                    'text' => $CampoOpcion->valor,
                    'dias' => $rt->getNumberDays() ?: 1,
                ];
            }

            return $json->success($data);
        } catch (Throwable $th) {
            return $json->exception($th);
        }
    }

    #[Route('', name: 'updateTimes', methods: ['PUT'])]
    public function updateTimes(
        Request $Request,
        jsonResponseService $json,
        Connection $Connection,
        TranslatorInterface $translator,
        PqrFormService $pqrFormService,
        PqrResponseTimeRepository $pqrResponseTimeRepository,
        EntityManagerInterface $em,
    ): Response {
        $Connection->beginTransaction();
        try {
            if (!$id = $Request->request->get('fk_field_time', 0)) {
                $trans = $translator->trans("falta_identificador_tiempo_respuesta");
                throw new MissingParameterException($trans);
            }

            $pqrFormService->save([
                'fk_field_time' => $id,
            ]);

            $i = 1;
            foreach ($Request->request->all('options') as $option) {
                $rt = $pqrResponseTimeRepository->find($option['id']);
                if ($rt) {
                    $rt->setNumberDays((int)$option['dias']);
                    $CampoOpcionesService = (new CampoOpciones($rt->getFkSysTipo()))->getService();
                    $CampoOpcionesService->save(['orden' => $i]);
                }
                $i++;
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
