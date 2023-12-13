<?php

namespace App\Bundles\pqr\Services;

use App\Bundles\pqr\formatos\pqr_calificacion\FtPqrCalificacion;
use App\Bundles\pqr\formatos\pqr_respuesta\FtPqrRespuesta;
use App\Bundles\pqr\Services\models\PqrForm;
use App\Bundles\pqr\Services\models\PqrHistory;
use App\Bundles\pqr\Services\models\PqrNotyMessage;
use App\services\correo\EmailSaia;
use App\services\correo\SendEmailSaia;
use App\services\models\ModelService\ModelService;
use Saia\controllers\anexos\FileJson;
use Saia\controllers\CryptController;
use Saia\controllers\DateController;
use Saia\controllers\DistributionService;
use Saia\controllers\documento\Transfer;
use Saia\controllers\functions\CoreFunctions;
use Saia\models\anexos\Anexos;
use Saia\models\BuzonSalida;
use Saia\models\Tercero;

class FtPqrRespuestaService extends ModelService
{
    private PqrForm $PqrForm;

    const OPTION_EMAIL_RESPUESTA = 1;
    const OPTION_EMAIL_CALIFICACION = 2;

    public function __construct(FtPqrRespuesta $Ft)
    {
        parent::__construct($Ft);
        $this->PqrForm = PqrForm::getInstance();

    }

    public function getModel(): FtPqrRespuesta
    {
        return $this->Model;
    }


    /**
     * Verifica si los email son validos
     *
     * @return boolean
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
     * @param string  $description
     * @param integer $type
     * @return boolean
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
            'descripcion'    => $description
        ];

        $PqrHistoryService = (new PqrHistory)->getService();
        if (!$PqrHistoryService->save($history)) {
            $this->getErrorManager()->setMessage(
                $PqrHistoryService->getErrorManager()->getMessage()
            );
            return false;
        }

        return true;
    }

    /**
     * Registra la distribucion
     *
     * @return boolean
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
            $recogida
        );

        return true;
    }

    /**
     * Valida si la respuesta se envia por E-mail
     *
     * @return boolean
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
     * @return boolean
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function transferCopiaInterna(): bool
    {
        if ($this->getModel()->copia_interna) {
            $Transfer = new Transfer(
                $this->getModel()->getDocument(),
                $this->getFuncionario()->getCode(),
                BuzonSalida::NOMBRE_COPIA
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
     * @return boolean
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

        $message = "Cordial Saludo,<br/><br/>Adjunto encontrara la respuesta a la solicitud de {$FtPqrRespuesta->PqrForm->label} con número de radicado $DocumentoPqr->numero.<br/><br/>";
        $subject = "Respuesta solicitud de {$this->PqrForm->label} # $DocumentoPqr->numero";

        if ($PqrNotyMessage = PqrNotyMessage::findByAttributes([
            'name' => 'f2_email_respuesta'
        ])) {
            $message = PqrNotyMessageService::resolveVariables($PqrNotyMessage->message_body, $FtPqr);
            $subject = PqrNotyMessageService::resolveVariables($PqrNotyMessage->subject, $FtPqr);
        }

        if ($FtPqrRespuesta->sol_encuesta) {
            $url = $this->getUrlEncuesta();
            $message .= "Califica nuestro servicio haciendo clic en el siguiente enlace: <a href='$url'>Calificar el servicio</a> .<br/><br/>";
        }

        $DocumentoRespuesta = $FtPqrRespuesta->getDocument();
        $anexos[] = new FileJson($DocumentoRespuesta->getPdfJson());

        $DocumentoService = $DocumentoRespuesta->getService();
        if ($records = $DocumentoService->getAllFilesAnexos(true)) {
            foreach ($records as $Anexos) {
                $anexos[] = new FileJson($Anexos->ruta);
            }
        }

        $EmailSaia = (new EmailSaia())
            ->subject($subject)
            ->htmlWithTemplate($message)
            ->to($FtPqrRespuesta->getTercero()->getEmail())
            ->addAttachments($anexos)
            ->saveShipmentTraceability($DocumentoRespuesta->getPK())
            ->defineCallbackClassName(FtPqrRespuestaEmailCallback::class);

        $emailCopy = $this->getCopyEmail();
        if ($emailCopy) {
            $EmailSaia->cc(...$emailCopy);
        }

        $description = "Se le notificó a: {$FtPqrRespuesta->getTercero()->getEmail()}";
        if ($emailCopy) {
            $texCopia = implode(", ", $emailCopy);
            $description .= " con copia a: ($texCopia)";
        }

        $EmailSaia->defineParamsCallbackClassName([
            'option'      => self::OPTION_EMAIL_RESPUESTA,
            'idft'        => $this->getModel()->getPK(),
            'descripcion' => $description,
            'tipo'        => PqrHistory::TIPO_NOTIFICACION
        ]);

        (new SendEmailSaia($EmailSaia))->send();

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
    private function getUrlEncuesta(): string
    {
        $params = CryptController::encrypt(json_encode([
            'ft_pqr_respuesta' => $this->getModel()->getPK(),
            'anterior'         => $this->getModel()->getDocument()->getPK()
        ]));

        return PqrFormService::getUrlWsCalificacion() . "?d=$params";
    }

    /**
     * Solicita via email la encuesta de satisfaccion
     *
     * @return boolean
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function requestSurvey(): bool
    {
        $email = $this->getModel()->getTercero()->getEmail();
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

        $EmailSaia = (new EmailSaia())
            ->subject("Queremos conocer tu opinión! (Solicitud de {$this->PqrForm->label} # $DocumentoPqr->numero)")
            ->htmlWithTemplate($message)
            ->to($email)
            ->saveShipmentTraceability($this->getModel()->getDocument()->getPK())
            ->defineCallbackClassName(FtPqrRespuestaEmailCallback::class)
            ->defineParamsCallbackClassName([
                'option'      => self::OPTION_EMAIL_CALIFICACION,
                'idft'        => $this->getModel()->getPK(),
                'descripcion' => $description,
                'tipo'        => PqrHistory::TIPO_CALIFICACION
            ]);

        (new SendEmailSaia($EmailSaia))->send();

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

}
