<?php

namespace App\Bundles\pqr\Services;

use App\Bundles\pqr\Entity\PqrForm as PqrFormEntity;
use App\Bundles\pqr\Entity\PqrHistory as PqrHistoryEntity;
use App\Bundles\pqr\Entity\PqrNotyMessage as PqrNotyMessageEntity;
use App\Bundles\pqr\formatos\pqr_calificacion\FtPqrCalificacion;
use App\Bundles\pqr\formatos\pqr_respuesta\FtPqrRespuesta;
use App\Bundles\pqr\Repository\PqrFormRepository;
use App\Bundles\pqr\Repository\PqrHistoryRepository;
use App\Bundles\pqr\Repository\PqrNotyMessageRepository;
use App\EventSubscriber\Mailer\MailSubscriber;
use App\services\documento\DocumentoExpuestoService;
use App\services\Gaufrette\Gaufrette\FilesystemForJson;
use App\services\models\ModelService\ModelService;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Saia\controllers\DistributionService;
use Saia\controllers\documento\Transfer;
use Saia\controllers\functions\CoreFunctions;
use Saia\models\BuzonSalida;
use Saia\models\documento\DocumentoExpuesto;
use Saia\models\formatos\Formato;
use Saia\models\tarea\TareaEstado;
use Saia\models\Tercero;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class FtPqrRespuestaService extends ModelService
{
    public const int OPTION_EMAIL_RESPUESTA = 1;
    public const int OPTION_EMAIL_CALIFICACION = 2;

    public function __construct(FtPqrRespuesta $Ft)
    {
        parent::__construct($Ft);
    }

    public function getModel(): FtPqrRespuesta
    {
        return $this->Model;
    }


    /**
     * Verifica si los email son validos
     *
     * @return bool
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function validEmails(): bool
    {
        $email = $this->getModel()->getTercero()->getEmail();
        if (!$email) {
            $this->getErrorManager()->setMessage("Debe ingresar el email (Destino)");

            return false;
        }

        if (!CoreFunctions::isEmailValid($email)) {
            $this->getErrorManager()->setMessage("El email ($email) NO es valido");

            return false;
        }

        if ($emailCopy = $this->getCopyEmail()) {
            foreach ($emailCopy as $copia) {
                if (!$copia) {
                    $this->getErrorManager()->setMessage("Debe ingresar el email (Con copia a)");

                    return false;
                }

                if (!CoreFunctions::isEmailValid($copia)) {
                    $this->getErrorManager()->setMessage("El email en copia externa ($copia) NO es valido");

                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Se crea un registro en el historial
     *
     * @param string $description
     * @param int $type
     * @return bool
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function saveHistory(string $description, int $type): bool
    {
        $history = [
            'fecha'          => date('Y-m-d H:i:s'),
            'idft'           => $this->getModel()->getFtPqr()->getPK(),
            'fk_funcionario' => $this->getFuncionario()->getPK(),
            'tipo'           => $type,
            'idfk'           => $this->getModel()->getPK(),
            'descripcion'    => $description,
        ];

        $entity = new PqrHistoryEntity();
        $entity->setIdft((int)$history['idft']);
        $entity->setFkFuncionario((int)$history['fk_funcionario']);
        $entity->setTipo((int)$history['tipo']);
        $entity->setIdfk((int)$history['idfk']);
        $entity->setDescripcion((string)$history['descripcion']);

        try {
            $this->getPqrHistoryRepository()->create($entity);
        } catch (\Throwable $e) {
            $this->getErrorManager()->setMessage($e->getMessage());

            return false;
        }

        return true;
    }

    /**
     * Registra la distribucion
     *
     * @return bool
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function saveDistribution(): bool
    {
        switch ((int)$this->getModel()->getKeyField('tipo_distribucion')) {
            case FtPqrRespuesta::DISTRIBUCION_RECOGIDA_ENTREGA:
                $recogida = DistributionService::ESTADO_RECOGIDA;
                $estado = DistributionService::DISTRIBUCION_POR_RECEPCIONAR;
                break;

            case FtPqrRespuesta::DISTRIBUCION_SOLO_ENTREGA:
                $recogida = DistributionService::ESTADO_ENTREGA;
                $estado = DistributionService::DISTRIBUCION_PENDIENTE;
                break;

            case FtPqrRespuesta::DISTRIBUCION_NO_REQUIERE_MENSAJERIA:
            case FtPqrRespuesta::DISTRIBUCION_ENVIAR_EMAIL:
                $recogida = DistributionService::ESTADO_ENTREGA;
                $estado = DistributionService::DISTRIBUCION_FINALIZADA;
                break;

            default:
                $this->getErrorManager()->setMessage("Tipo de distribucion no definida");

                return false;
        }
        $DistributionService = new DistributionService($this->getModel()->getDocument());
        $DistributionService->start(
            $this->getModel()->dependencia,
            DistributionService::TIPO_INTERNO,
            $this->getModel()->destino,
            DistributionService::TIPO_EXTERNO,
            $estado,
            $recogida,
        );

        return true;
    }

    /**
     * Valida si la respuesta se envia por E-mail
     *
     * @return bool
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function sendByEmail(): bool
    {
        return (int)$this->getModel()->getKeyField('tipo_distribucion') == FtPqrRespuesta::DISTRIBUCION_ENVIAR_EMAIL;
    }

    /**
     * Transfiere a los ingresados
     * en copia interna
     *
     * @return bool
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function transferCopiaInterna(): bool
    {
        if ($this->getModel()->copia_interna) {
            $Transfer = new Transfer(
                $this->getModel()->getDocument(),
                $this->getFuncionario()->getCode(),
                BuzonSalida::NOMBRE_COPIA,
            );
            $destinations = explode(',', $this->getModel()->copia_interna);
            $Transfer->setDestination($destinations);
            $Transfer->setDestinationType(Transfer::DESTINATION_TYPE_ROLE);
            $Transfer->execute();
        }

        return true;
    }

    /**
     * Notifica la respuesta via Email
     *
     * @return bool
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function notifyEmail(): bool
    {
        if (!$this->sendByEmail()) {
            return true;
        }

        $FtPqrRespuesta = $this->getModel();
        $FtPqr = $FtPqrRespuesta->getFtPqr();
        $DocumentoPqr = $FtPqr->getDocument();

        $pqrFormLabel = $this->getPqrFormEntity()->getLabel();
        $message = "Cordial Saludo,<br/><br/>Adjunto encontrara la respuesta a la solicitud de {$pqrFormLabel} con número de radicado $DocumentoPqr->numero.<br/><br/>";
        $subject = "Respuesta solicitud de {$pqrFormLabel} # $DocumentoPqr->numero";

        if ($notyMessage = $this->getPqrNotyMessageRepository()->findByName('f2_email_respuesta')) {
            $message = PqrNotyMessageService::resolveVariables($notyMessage->getMessageBody() ?? '', $FtPqr);
            $subject = PqrNotyMessageService::resolveVariables($notyMessage->getSubject() ?? '', $FtPqr);
        }

        if ($FtPqrRespuesta->sol_encuesta) {
            $url = $this->getUrlEncuesta();
            $message .= "Califica nuestro servicio haciendo clic en el siguiente enlace: <a href='$url'>Calificar el servicio</a> .<br/><br/>";
        }

        $email = (new Email());

        $DocumentoRespuesta = $FtPqrRespuesta->getDocument();
        $file = FilesystemForJson::getFileJson($DocumentoRespuesta->getPdfJson());
        $email->attach($file->getContent(), basename($file->getName()));

        $DocumentoService = $DocumentoRespuesta->getService();
        if ($records = $DocumentoService->getAllFilesAnexos(true)) {
            foreach ($records as $Anexos) {
                $file = FilesystemForJson::getFileJson($Anexos->ruta);
                $email->attach($file->getContent(), basename($file->getName()));
            }
        }

        $email
            ->subject($subject)
            ->html($message)
            ->to(new Address($FtPqrRespuesta->getTercero()->getEmail(), $FtPqrRespuesta->getTercero()->getName()));

        $emailCopy = $this->getCopyEmail();
        if ($emailCopy) {
            $email->cc(...$emailCopy);
        }

        $description = "Se le notificó a: {$FtPqrRespuesta->getTercero()->getEmail()}";
        if ($emailCopy) {
            $texCopia = implode(", ", $emailCopy);
            $description .= " con copia a: ($texCopia)";
        }

        $params = [
            'isRespuetaPqr' => 1,
            'documentId'    => $DocumentoRespuesta->getPK(),
            'option'        => self::OPTION_EMAIL_RESPUESTA,
            'idft'          => $this->getModel()->getPK(),
            'descripcion'   => $description,
            'tipo'          => PqrHistoryEntity::TIPO_NOTIFICACION,
        ];

        $email->getHeaders()->addTextHeader(
            MailSubscriber::HEADER_METADATA,
            json_encode($params),
        );

        $this->serviceLocator->getMailerService()->send($email, 'pqr.respuesta.respuesta');

        return true;
    }

    /**
     * Obtiene los email de copia
     *
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    private function getCopyEmail(): array
    {
        $emails = [];
        if ($this->getModel()->copia) {
            $records = explode(',', $this->getModel()->copia);
            foreach ($records as $destino) {
                $emails[] = (new Tercero($destino))->getEmail();
            }
        }

        return $emails;
    }

    /**
     * Obtiene la URL del ws para calificar el servicio o encuesta
     *
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    protected function getUrlEncuesta(): string
    {
        $Formato = Formato::findByAttributes([
            'nombre' => 'pqr_calificacion',
        ]);

        $DocumentoExpuesto = DocumentoExpuestoService::createOrUpdate(
            $this->getModel()->getDocument(),
            DocumentoExpuesto::TIPO_HIJO,
            $Formato,
            24 * 7, //1 Semana
        );

        return $DocumentoExpuesto->getUrl();
    }

    /**
     * Solicita via email la encuesta de satisfaccion
     *
     * @return bool
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function requestSurvey(): bool
    {
        $tercero = $this->getModel()->getTercero();
        $email = $tercero->getEmail();
        if (!CoreFunctions::isEmailValid($email)) {
            $this->getErrorManager()->setMessage("El email ($email) NO es valido");

            return false;
        }

        $DocumentoPqr = $this->getModel()->getFtPqr()->getDocument();

        $url = $this->getUrlEncuesta();
        $message = "Cordial Saludo,<br/><br/>
        Nos gustaría recibir tus comentarios sobre el servicio que has recibido por parte de nuestro equipo.<br/><a href='$url'>Calificar el servicio</a>";

        $nameFormat = $this->getModel()->getFormat()->etiqueta;
        $description = "Se solicita la calificación de la ($nameFormat) # {$this->getModel()->getDocument()->numero} al e-mail: ($email)";

        $EmailSaia = (new Email())
            ->subject("Queremos conocer tu opinión! (Solicitud de {$this->getPqrFormEntity()->getLabel()} # $DocumentoPqr->numero)")
            ->html($message)
            ->to(new Address($tercero->getEmail(), $tercero->getName()));

        $params = [
            'isRespuetaPqr' => 1,
            'documentId'    => $this->getModel()->getDocument()->getPK(),
            'option'        => self::OPTION_EMAIL_CALIFICACION,
            'idft'          => $this->getModel()->getPK(),
            'descripcion'   => $description,
            'tipo'          => PqrHistoryEntity::TIPO_CALIFICACION,
        ];

        $EmailSaia->getHeaders()->addTextHeader(
            MailSubscriber::HEADER_METADATA,
            json_encode($params),
        );

        $this->serviceLocator->getMailerService()->send($EmailSaia, 'pqr.respuesta.encuesta');


        return true;
    }

    /**
     * Obtiene la Calificaciones
     * Utilizado en reporteFunciones.php
     *
     * @return FtPqrCalificacion[]
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function getFtPqrCalificacion(): array
    {
        $data = [];
        if ($records = $this->getModel()->getFtPqrCalificaciones()) {
            foreach ($records as $FtPqrCalificacion) {
                if (!$FtPqrCalificacion->getDocument()->isActive()) {
                    $data[] = $FtPqrCalificacion;
                }
            }
        }

        return $data;
    }

    /**
     * Cierra todas las tareas Pendientes
     *
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2024-06-25
     */
    public function closeTask(): void
    {
        $records = $this->getModel()->getFtPqr()->getDocument()->getService()->getTasks();
        if (!$records) {
            return;
        }

        foreach ($records as $Tarea) {
            $TareaService = $Tarea->getService();
            $valor = $TareaService->getState()->valor;
            if (
                $valor != TareaEstado::REALIZADA &&
                $valor != TareaEstado::CANCELADA
            ) {
                $save = $TareaService->setState(
                    TareaEstado::REALIZADA,
                    $this->getFuncionario()->getPK(),
                    'La tarea se cambia a estado realizada al radicar la COMUNICACIÓN EXTERNA (PQRSF)',
                );

                if (!$save) {
                    $trans = $this->serviceLocator->getTranslator()->trans(
                        "no_fue_posible_cambiar_estado_tarea",
                        ['taskId' => $Tarea->getPK()],
                    );
                    throw new RuntimeException($trans);
                }
            }
        }
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return $this->serviceLocator->getEntityManager();
    }

    private function getPqrFormEntity(): PqrFormEntity
    {
        return $this->getEntityManager()->getRepository(PqrFormEntity::class)->findActiveOrFail();
    }

    private function getPqrHistoryRepository(): PqrHistoryRepository
    {
        return $this->getEntityManager()->getRepository(PqrHistoryEntity::class);
    }

    private function getPqrNotyMessageRepository(): PqrNotyMessageRepository
    {
        return $this->getEntityManager()->getRepository(PqrNotyMessageEntity::class);
    }

}
