<?php

namespace App\Bundles\pqr\EventSubscriber;

use App\Bundles\pqr\formatos\pqr\FtPqr;
use App\Bundles\pqr\helpers\UtilitiesPqr;
use App\Bundles\pqr\Services\models\PqrForm;
use App\Bundles\pqr\Services\models\PqrHistory;
use App\Event\tarea\TaskCreatedEvent;
use App\Event\tarea\TaskDeletedEvent;
use App\Event\tarea\TaskStatusCreatedEvent;
use App\services\models\tareas\TareaService;
use Exception;
use Saia\models\documento\Documento;
use Saia\models\tarea\Tarea;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


class PqrSubscriber implements EventSubscriberInterface
{

    public static function getSubscribedEvents(): array
    {
        return [
            TaskCreatedEvent::class => [
                ['onTaskCreated', -1]
            ],
            TaskDeletedEvent::class => [
                ['onTaskDeletedEvent', -1]
            ],
            TaskStatusCreatedEvent::class => [
                ['onTaskStatusCreatedEvent', -1]
            ]
        ];
    }

    /**
     * evento a ejecutar despues de crear la tarea
     *
     * @param TaskCreatedEvent $TaskCreatedEvent
     * @return bool
     * @throws Exception
     * @author Andres Agudelo <jhon.valencia@cerok.com>
     * @date   2021-02-03
     */
    public function onTaskCreated(TaskCreatedEvent $TaskCreatedEvent): bool
    {
        $TareaService = $TaskCreatedEvent->getService();
        $description = "Se crea la tarea: {$TareaService->getModel()->nombre}";

        return $this->saveHistory($TareaService, $description);
    }

    /**
     * Evento a ejecutar despues de eliminar una tarea
     *
     * @param TaskDeletedEvent $TaskDeletedEvent
     * @return bool
     * @throws Exception
     * @author Andres Agudelo <andres.agudelo@cerok.com> @date 2021-03-17
     */
    public function onTaskDeletedEvent(TaskDeletedEvent $TaskDeletedEvent): bool
    {
        $TareaService = $TaskDeletedEvent->getService();
        $description = "Se elimina la tarea: {$TareaService->getModel()->nombre}";

        return $this->saveHistory($TareaService, $description);
    }

    /**
     * Evento a ejecutar despues de crear un estado de la tarea
     *
     * @param TaskStatusCreatedEvent $TaskStatusCreatedEvent
     * @return bool
     * @throws Exception
     * @author Andres Agudelo <andres.agudelo@cerok.com> @date 2021-03-18
     */
    public function onTaskStatusCreatedEvent(TaskStatusCreatedEvent $TaskStatusCreatedEvent): bool
    {
        $TareaEstadoService = $TaskStatusCreatedEvent->getService();
        $TareaService = $TareaEstadoService->getTarea()->getService();
        $description = "Se actualiza el estado de la tarea ({$TareaService->getModel()->nombre}) a : {$TareaEstadoService->getModel()->getValueLabel('valor')}";

        return $this->saveHistory($TareaService, $description);
    }

    /**
     * Actualiza el historial de cambios
     *
     * @param TareaService $TareaService
     * @param string       $description
     * @return bool
     * @throws Exception
     * @author Andres Agudelo <andres.agudelo@cerok.com> @date 2021-03-18
     */
    private function saveHistory(TareaService $TareaService, string $description): bool
    {
        if ($TareaService->getModel()->relacion == Tarea::RELACION_DOCUMENTO) {
            $Documento = new Documento($TareaService->getModel()->relacion_id);
            if ($Documento->formato_idformato == PqrForm::getInstance()->fk_formato) {
                $history = [
                    'fecha' => date('Y-m-d H:i:s'),
                    'idft' => $Documento->getFt()->getPK(),
                    'fk_funcionario' => $TareaService->getFuncionario()->getPK(),
                    'tipo' => PqrHistory::TIPO_TAREA,
                    'idfk' => $TareaService->getModel()->getPK(),
                    'descripcion' => $description
                ];

                $PqrHistoryService = (new PqrHistory)->getService();
                if (!$PqrHistoryService->save($history)) {
                    throw new Exception($PqrHistoryService->getErrorMessage(), 200);
                }

                if (!$this->updateEstado($Documento)) {
                    throw new Exception("No fue posible actualizar el estado de la solicitud", 200);
                }
            }
        }
        return true;
    }

    /**
     * Actualiza el estado de la PQR
     *
     * @param Documento $Documento
     * @return boolean
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    private function updateEstado(Documento $Documento): bool
    {
        $estado = FtPqr::ESTADO_PENDIENTE;

        $data = UtilitiesPqr::getFinishTotalTask($Documento);
        if ($data['total']) {
            $estado = $data['total'] == $data['finish'] ?
                FtPqr::ESTADO_TERMINADO : FtPqr::ESTADO_PROCESO;
        }

        $Ft = $Documento->getFt();

        if ($estado == FtPqr::ESTADO_PENDIENTE && $Ft->sys_estado != FtPqr::ESTADO_PROCESO) {
            if ($records = $Ft->PqrRespuesta) {
                foreach ($records as $FtPqrRespuesta) {
                    if (!$FtPqrRespuesta->getDocument()->isDeleted()) {
                        $estado = FtPqr::ESTADO_PROCESO;
                        break;
                    }
                }
            }
        }

        return $Ft->getService()->changeStatus($estado);
    }
}