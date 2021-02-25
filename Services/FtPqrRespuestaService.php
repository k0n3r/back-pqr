<?php

namespace App\Bundles\pqr\Services;

use App\Bundles\pqr\formatos\pqr_calificacion\FtPqrCalificacion;
use App\Bundles\pqr\formatos\pqr_respuesta\FtPqrRespuesta;
use App\Bundles\pqr\helpers\UtilitiesPqr;
use App\Bundles\pqr\Services\models\PqrForm;
use App\Bundles\pqr\Services\models\PqrHistory;
use App\Bundles\pqr\Services\models\PqrNotyMessage;
use App\services\models\ModelService;
use Saia\controllers\anexos\FileJson;
use Saia\controllers\CryptController;
use Saia\controllers\DateController;
use Saia\controllers\DistributionService;
use Saia\controllers\documento\Transfer;
use Saia\controllers\SendMailController;
use Saia\controllers\SessionController;
use Saia\core\model\Model;
use Saia\models\anexos\Anexos;
use Saia\models\BuzonSalida;
use Saia\models\Tercero;

class FtPqrRespuestaService extends ModelService
{
    private PqrForm $PqrForm;

    public function __construct(FtPqrRespuesta $Ft)
    {
        parent::__construct($Ft);

        $this->setFuncionario(SessionController::getUser());
        $this->PqrForm = PqrForm::getInstance();

    }

    public function getModel(): Model
    {
        return $this->Model;
    }

    /**
     * Obtiene el radicado
     *
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function getRadicado(): string
    {
        return DateController::convertDate($this->getModel()->Documento->fecha, 'Ymd')
            . " - " . $this->getModel()->Documento->numero;
    }

    /**
     * Obtiene fecha y nombre de la ciudad
     *
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function getFechaCiudad(): string
    {
        return $this->getModel()->Municipio->nombre . ", " . strftime("%d de %B de %Y", strtotime($this->getModel()->Documento->fecha));
    }

    /**
     * Obtiene los datos del remitente
     *
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function getInfoDestino(): string
    {
        $code = '';
        if ($this->getModel()->Tercero) {
            $code .= $this->getModel()->Tercero->titulo ? $this->getModel()->Tercero->titulo . "<br/>" : '';
            $code .= $this->getModel()->Tercero->nombre . "<br/>";
            $code .= $this->getModel()->Tercero->cargo ? $this->getModel()->Tercero->cargo . "<br/>" : '';
            $code .= $this->getModel()->Tercero->direccion ? $this->getModel()->Tercero->direccion . "<br/>" : '';
            $code .= $this->getModel()->Tercero->telefono ? $this->getModel()->Tercero->telefono . "<br/>" : '';
        }

        return $code;
    }

    /**
     * Obtiene el texto de despedida
     *
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function getDespedida(): string
    {
        return $this->getModel()->getKeyField('despedida') == FtPqrRespuesta::OTRA_DESPEDIDA ?
            $this->getModel()->otra_despedida : $this->getModel()->getFieldValue('despedida');
    }

    /**
     * Obtiene mas informacion que va en el mostrar
     *
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function getOtherData(): string
    {
        $data = '';
        if ($this->getModel()->anexos_fisicos) {
            $data .= "Anexos físicos: {$this->getModel()->anexos_fisicos}<br/>";
        }

        if ($anexosDigitales = $this->getNameAnexosDigitales()) {
            $data .= "Anexos digitales: {$anexosDigitales}<br/>";
        }

        if ($copiaExterna = $this->getNameCopiaExterna()) {
            $data .= "Copia externa: {$copiaExterna}<br/>";
        }

        $data .= "Proyectó: {$this->getCreador()}";

        return $data;
    }

    /**
     * Obtiene los nombres de los anexos
     *
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    private function getNameAnexosDigitales(): string
    {
        $id = $this->getModel()->Formato->getField('anexos_digitales')->getPK();

        $names = Anexos::findColumn('etiqueta', [
            'documento_iddocumento' => $this->getModel()->Documento->getPK(),
            'campos_formato' => $id
        ]);

        return $names ? implode(', ', $names) : '';
    }

    /**
     * Obtiene los nombres de las personas a quien va con copia externa
     *
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    private function getNameCopiaExterna(): string
    {
        if (!$this->getModel()->copia) {
            return '';
        }

        $names = [];
        $records = explode(',', $this->getModel()->copia);
        foreach ($records as $destino) {
            $names[] = (new Tercero($destino))->nombre;
        }

        return implode(', ', $names);
    }

    /**
     * Obtiene el nombre del creador o de quien proyectó
     *
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    private function getCreador(): string
    {
        return $this->getModel()->Documento->Funcionario->getName();
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
        $email = $this->getModel()->Tercero->correo;
        if (!$email) {
            $this->errorMessage = "Debe ingresar el email (Destino)";
            return false;
        }

        if (!UtilitiesPqr::isEmailValid($email)) {
            $this->errorMessage = "El email ({$email}) NO es valido";
            return false;
        }

        if ($emailCopy = $this->getCopyEmail()) {
            foreach ($emailCopy as $copia) {
                if (!$copia) {
                    $this->errorMessage = "Debe ingresar el email (Con copia a)";
                    return false;
                }

                if (!UtilitiesPqr::isEmailValid($copia)) {
                    $this->errorMessage = "El email en copia externa ({$copia}) NO es valido";
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
            'fecha' => date('Y-m-d H:i:s'),
            'idft' => $this->getModel()->FtPqr->getPK(),
            'fk_funcionario' => $this->getModel()->Funcionario->getPK(),
            'tipo' => $type,
            'idfk' => $this->getModel()->getPK(),
            'descripcion' => $description
        ];

        $PqrHistoryService = (new PqrHistory)->getService();
        if (!$PqrHistoryService->save($history)) {
            $this->errorMessage = $PqrHistoryService->getErrorMessage();
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
                $this->errorMessage = "Tipo de distribucion no definida";
                return false;
        }
        $DistributionService = new DistributionService($this->getModel()->Documento);
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
                $this->getModel()->Documento,
                $this->getModel()->Funcionario->funcionario_codigo,
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

        $DocumentoPqr = $this->getModel()->FtPqr->Documento;
        $message = "Cordial Saludo,<br/><br/>Adjunto encontrara la respuesta a la solicitud de {$this->getModel()->PqrForm->label} con número de radicado {$DocumentoPqr->numero}.<br/><br/>";
        $subject = "Respuesta solicitud de {$this->PqrForm->label} # {$DocumentoPqr->numero}";

        if ($PqrNotyMessage = PqrNotyMessage::findByAttributes([
            'name' => 'f2_email_respuesta'
        ])) {
            $message = PqrNotyMessageService::resolveVariables($PqrNotyMessage->message_body, $this->getModel()->FtPqr);
            $subject = PqrNotyMessageService::resolveVariables($PqrNotyMessage->subject, $this->getModel()->FtPqr);
        }

        if ($this->getModel()->sol_encuesta) {
            $url = $this->getUrlEncuesta();
            $message .= "Califica nuestro servicio haciendo clic en el siguiente enlace: <a href='{$url}'>Calificar el servicio</a> .<br/><br/>";
        }

        $SendMailController = new SendMailController(
            $subject,
            $message
        );

        $SendMailController->setDestinations(
            SendMailController::DESTINATION_TYPE_EMAIL,
            [$this->getModel()->Tercero->correo]
        );

        if ($emailCopy = $this->getCopyEmail()) {
            $SendMailController->setCopyDestinations(
                SendMailController::DESTINATION_TYPE_EMAIL,
                $emailCopy
            );
        }

        $anexos = [];
        $File = new FileJson($this->getModel()->Documento->getPdfJson());
        $anexos[] = $File;

        if ($records = $this->getModel()->Documento->Anexos) {
            foreach ($records as $Anexo) {
                $anexos[] = new FileJson($Anexo->ruta);
            }
        }
        $SendMailController->setAttachments($anexos);

        $send = $SendMailController->send();
        if ($send !== true) {
            $log = [
                'error' => $send,
                'message' => "No fue posible notificar la Respuesta a la PQR # {$DocumentoPqr->numero}"
            ];
            UtilitiesPqr::notifyAdministrator(
                "No fue posible notificar la Respuesta a la PQR # {$DocumentoPqr->numero}",
                $log
            );

            $this->errorMessage = "No fue posible notificar la respuesta";
            return false;
        }

        $description = "Se le notificó a: (" . implode(", ", $SendMailController->getDestinations()) . ")";
        if ($copia = $SendMailController->getCopyDestinations()) {
            $texCopia = implode(", ", $copia);
            $description .= " con copia a: ({$texCopia})";
        }
        $tipo = PqrHistory::TIPO_NOTIFICACION;

        return $this->saveHistory($description, $tipo);
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
                $emails[] = (new Tercero($destino))->correo;
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
            'anterior' => $this->getModel()->Documento->getPK()
        ]));

        return PqrFormService::URLWSCALIFICACION . "?d={$params}";
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
        $email = $this->getModel()->Tercero->correo;
        if (!UtilitiesPqr::isEmailValid($email)) {
            $this->errorMessage = "El email ({$email}) NO es valido";
            return false;
        }

        $nameFormat = $this->getModel()->getFormat()->etiqueta;
        $DocumentoPqr = $this->getModel()->FtPqr->Documento;

        $url = $this->getUrlEncuesta();
        $message = "Cordial Saludo,<br/><br/>
        Nos gustaría recibir tus comentarios sobre el servicio que has recibido por parte de nuestro equipo.<br/><a href='{$url}'>Calificar el servicio</a>";

        $SendMailController = new SendMailController(
            "Queremos conocer tu opinión! (Solicitud de {$this->PqrForm->label} # {$DocumentoPqr->numero})",
            $message
        );

        $SendMailController->setDestinations(
            SendMailController::DESTINATION_TYPE_EMAIL,
            [$email]
        );

        $send = $SendMailController->send();
        if ($send !== true) {
            $message = "No fue posible solicitar la calificacion de la ({$nameFormat}) # {$this->getModel()->Documento->numero}";
            $log = [
                'error' => $send,
                'message' => $message
            ];

            UtilitiesPqr::notifyAdministrator(
                $message,
                $log
            );

            $this->errorMessage = $message;
            return false;
        }

        $description = "Se solicita la calificación de la ({$nameFormat}) # {$this->getModel()->Documento->numero} al e-mail: ({$email})";
        $tipo = PqrHistory::TIPO_CALIFICACION;

        return $this->saveHistory($description, $tipo);
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
        if ($records = $this->getModel()->FtPqrCalificacion) {
            foreach ($records as $FtPqrCalificacion) {
                if (!$FtPqrCalificacion->Documento->isActive()) {
                    $data[] = $FtPqrCalificacion;
                }
            }
        }
        return $data;
    }

}