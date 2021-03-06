<?php

namespace App\Bundles\pqr\EventSubscriber;

use App\Bundles\pqr\Services\controllers\TaskEvents;
use App\Bundles\pqr\Services\models\PqrForm;
use App\Bundles\pqr\Services\models\PqrHistory;
use App\Event\tarea\TaskCreatedEvent;
use Exception;
use Saia\models\documento\Documento;
use Saia\models\tarea\Tarea;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


class PqrSubscriber implements EventSubscriberInterface
{

    public static function getSubscribedEvents(): array
    {
        return [
            TaskCreatedEvent::class => 'onTaskCreated'
        ];
    }

    /**
     * evento a ejecutar despues de crear la tarea
     *
     * @param TaskCreatedEvent $TaskCreatedEvent
     * @return bool
     * @throws Exception
     * @author jhon sebastian valencia <jhon.valencia@cerok.com>
     * @date   2021-02-03
     */
    public function onTaskCreated(TaskCreatedEvent $TaskCreatedEvent): bool
    {
        $TareaService = $TaskCreatedEvent->getService();
        if ($TareaService->getModel()->relacion == Tarea::RELACION_DOCUMENTO) {
            $Documento = new Documento($TareaService->getModel()->relacion_id);
            if ($Documento->formato_idformato == PqrForm::getInstance()->fk_formato) {
                $history = [
                    'fecha' => date('Y-m-d H:i:s'),
                    'idft' => $Documento->getFt()->getPK(),
                    'fk_funcionario' => $TaskCreatedEvent->getFuncionario()->getPK(),
                    'tipo' => PqrHistory::TIPO_TAREA,
                    'idfk' => $TareaService->getModel()->getPK(),
                    'descripcion' => "Se crea la tarea: {$TareaService->getModel()->nombre}"
                ];

                $PqrHistoryService = (new PqrHistory)->getService();
                if (!$PqrHistoryService->save($history)) {
                    throw new Exception($PqrHistoryService->getErrorMessage(), 200);
                }

                if (!TaskEvents::updateEstado($Documento)) {
                    throw new Exception("No fue posible actualizar el estado de la solicitud", 200);
                }
            }
        }
        return true;
    }
}