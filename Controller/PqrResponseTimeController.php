<?php

namespace App\Bundles\pqr\Controller;

use App\Bundles\pqr\Services\models\PqrForm;
use App\Bundles\pqr\Services\models\PqrResponseTime;
use App\Exception\MissingParameterException;
use App\Service\JsonResponseService;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

#[Route('/responseTimes', name: 'responseTimes_')]
class PqrResponseTimeController extends AbstractController
{
    #[Route('/field/{id}', name: 'timesForField', methods: ['GET'])]
    public function timesForField(
        int $id,
        jsonResponseService $json,
    ): Response {
        try {
            $record = PqrResponseTime::findAllByAttributes([
                'fk_campo_opciones' => $id,
                'active'            => 1,
            ]);

            $data = [];
            $keys = [];
            $mayor = 0;
            foreach ($record as $PqrResponseTime) {
                $CampoOpcion = $PqrResponseTime->getCampoOpcionForSysTipo();

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
                    'id'   => $PqrResponseTime->getPK(),
                    'text' => $CampoOpcion->valor,
                    'dias' => (int)$PqrResponseTime->number_days ?: 1,
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
    ): Response {
        $Connection->beginTransaction();
        try {
            if (!$id = $Request->get('fk_field_time', 0)) {
                $trans = $translator->trans("falta_identificador_tiempo_respuesta");
                throw new MissingParameterException($trans);
            }

            $PqrForm = PqrForm::getInstance();
            $PqrForm->getService()->save([
                'fk_field_time' => $id,
            ]);

            $options = $Request->get('options');
            $i = 1;
            foreach ($options as $option) {
                $PqrResponseTimeService = (new PqrResponseTime($option['id']))->getService();
                $PqrResponseTimeService->save([
                    'number_days' => $option['dias'],
                ]);
                $CampoOpcionesService = $PqrResponseTimeService->getModel()->getCampoOpcionForSysTipo()->getService();
                $CampoOpcionesService->save([
                    'orden' => $i,
                ]);
                $i++;
            }

            $Connection->commit();

            return $json->success();
        } catch (Throwable $th) {
            $Connection->rollBack();

            return $json->exception($th);
        }
    }
}
