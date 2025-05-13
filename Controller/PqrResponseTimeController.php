<?php

namespace App\Bundles\pqr\Controller;

use App\Bundles\pqr\Services\models\PqrForm;
use App\Bundles\pqr\Services\models\PqrResponseTime;
use App\Exception\SaiaException;
use App\services\response\ISaiaResponse;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

#[Route('/responseTimes', name: 'responseTimes_')]
class PqrResponseTimeController extends AbstractController
{
    #[Route('/field/{id}', name: 'timesForField', methods: ['GET'])]
    public function timesForField(
        int $id,
        ISaiaResponse $saiaResponse,
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

            $saiaResponse->replaceData($data);
            $saiaResponse->setSuccess(1);
        } catch (Throwable $th) {
            $saiaResponse->setMessage($th->getMessage());
        }

        return $saiaResponse->getResponse();
    }

    #[Route('', name: 'updateTimes', methods: ['PUT'])]
    public function updateTimes(
        Request $Request,
        ISaiaResponse $saiaResponse,
        Connection $Connection,
    ): Response {
        $Connection->beginTransaction();
        try {
            if (!$id = $Request->get('fk_field_time', 0)) {
                throw new SaiaException("Falta el identificador del campo de los tiempos de respuesta");
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

            $saiaResponse->setSuccess(1);
            $Connection->commit();
        } catch (Throwable $th) {
            $Connection->rollBack();
            $saiaResponse->setMessage($th->getMessage());
        }

        return $saiaResponse->getResponse();
    }
}