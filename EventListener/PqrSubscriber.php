<?php

namespace App\Bundles\pqr\EventListener;

use App\Bundles\pqr\formatos\pqr\FtPqr;
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

                if (!PqrHistory::newRecord($history)) {
                    throw new \Exception("No fue posible guardar el historial del cambio", 200);
                }
                TaskEvents::updateEstado($Documento, FtPqr::ESTADO_PROCESO);
            }
        }
        return true;
    }
}