<?php

namespace App\Bundles\pqr\EventSubscriber;

use App\Bundles\pqr\formatos\pqr\FtPqr;
use App\Bundles\pqr\helpers\UtilitiesPqr;
use App\Bundles\pqr\Entity\PqrHistory as PqrHistoryEntity;
use App\Bundles\pqr\Service\PqrFormProvider;
use App\Bundles\pqr\Services\FtPqrRespuestaService;
use App\Bundles\pqr\Service\PqrHistoryService;
use App\Event\tarea\TaskCreatedEvent;
use App\Event\tarea\TaskDeletedEvent;
use App\Event\tarea\TaskStatusCreatedEvent;
use App\EventSubscriber\Mailer\ExtractInfoEmailTrait;
use App\services\models\tareas\TareaService;
use Exception;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Saia\models\documento\Documento;
use Saia\models\tarea\Tarea;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\Event\SentMessageEvent;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

readonly class PqrSubscriber implements EventSubscriberInterface
{
    use ExtractInfoEmailTrait;

    public function __construct(
        private LoggerInterface $logger,
        private TranslatorInterface $translator,
        private CacheInterface $cache,
        private PqrFormProvider $pqrFormProvider,
        private PqrHistoryService $pqrHistoryService,
    ) {
    }


    public static function getSubscribedEvents(): array
    {
        return [
            TaskCreatedEvent::class       => [
                ['onTaskCreated', -1],
            ],
            TaskDeletedEvent::class       => [
                ['onTaskDeletedEvent', -1],
            ],
            TaskStatusCreatedEvent::class => [
                ['onTaskStatusCreatedEvent', -1],
            ],
            SentMessageEvent::class       => ['onSent', -1],
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
     * @param string $description
     * @return bool
     * @throws Exception
     * @author Andres Agudelo <andres.agudelo@cerok.com> @date 2021-03-18
     */
    private function saveHistory(TareaService $TareaService, string $description): bool
    {
        if ($TareaService->getModel()->relacion == Tarea::RELACION_DOCUMENTO) {
            $Documento = new Documento($TareaService->getModel()->relacion_id);
            $pqrForm = $this->pqrFormProvider->getOrNull();
            if ($pqrForm && $Documento->formato_idformato == $pqrForm->getFkFormato()) {
                $this->pqrHistoryService->create([
                    'idft'           => $Documento->getFt()->getPK(),
                    'fk_funcionario' => $TareaService->getFuncionario()->getPK(),
                    'tipo'           => PqrHistoryEntity::TIPO_TAREA,
                    'idfk'           => $TareaService->getModel()->getPK(),
                    'descripcion'    => $description,
                ]);

                if (!$this->updateEstado($Documento)) {
                    $trans = $this->translator->trans("no_fue_posible_actualizar_estado_solicitud");
                    throw new RuntimeException($trans);
                }
            }
        }

        return true;
    }

    /**
     * Actualiza el estado de la PQR
     *
     * @param Documento $Documento
     * @return bool
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    private function updateEstado(Documento $Documento): bool
    {
        $estado = FtPqr::ESTADO_PENDIENTE;
        $data = UtilitiesPqr::getFinishTotalTask($Documento);

        $total = $data['total'] - $data['cancel'];
        if ($total) {
            $estado = $total == $data['finish'] ?
                FtPqr::ESTADO_TERMINADO : FtPqr::ESTADO_PROCESO;
        }

        $Ft = $Documento->getFt();

        if ($estado == FtPqr::ESTADO_PENDIENTE && $Ft->sys_estado != FtPqr::ESTADO_PROCESO) {
            if ($records = $Ft->getPqrRespuestas()) {
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

    public function onSent(SentMessageEvent $event): void
    {
        try {
            $message = $event->getMessage()->getOriginalMessage();
            $params = $this->extractDataFromHeaders($message->getHeaders());
            $isPqr = $params['isRespuetaPqr'] ?? null;

            if (!$isPqr) {
                return;
            }

            switch ($params['option']) {
                case FtPqrRespuestaService::OPTION_EMAIL_RESPUESTA:
                case FtPqrRespuestaService::OPTION_EMAIL_CALIFICACION:

                    $FtPqrRespuesta = UtilitiesPqr::getInstanceForFtIdPqrRespuesta($params['idft']);

                    if (!$FtPqrRespuesta->getService()->saveHistory($params['descripcion'], $params['tipo'])) {
                        $trans = $this->translator->trans("no_fue_posible_guardar_historial");
                        throw new RuntimeException($trans);
                    }
                    break;
            }

            $this->logger->info("[PQR] Email sent successfully", [
                'subject'    => $message->getSubject() ?? '(sin asunto)',
                'documentId' => $params['documentId'] ?? 0,
                'messageId'  => $event->getMessage()->getMessageId(),
            ]);
        } catch (Throwable $e) {
            $this->logger->error("[PQR] Error processing sent email", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
