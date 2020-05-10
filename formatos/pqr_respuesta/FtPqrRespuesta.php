<?php

namespace Saia\Pqr\formatos\pqr_respuesta;

use Exception;
use Saia\controllers\CryptController;
use Saia\Pqr\formatos\pqr\FtPqr;
use Saia\Pqr\Helpers\UtilitiesPqr;
use Saia\Pqr\controllers\WebserviceCalificacion;
use Saia\controllers\SendMailController;
use Saia\controllers\pdf\DocumentPdfGenerator;
use Saia\Pqr\formatos\pqr_calificacion\FtPqrCalificacion;

class FtPqrRespuesta extends FtPqrRespuestaProperties
{

    /**
     *
     * @var FtPqrCalificacion
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    protected $PqrCalificacion;

    public function __construct($id = null)
    {
        parent::__construct($id);
    }

    protected function defineMoreAttributes(): array
    {
        return [
            'relations' => [
                'FtPqr' => [
                    'model' => FtPqr::class,
                    'attribute' => 'idft_pqr',
                    'primary' => 'ft_pqr',
                    'relation' => self::BELONGS_TO_ONE
                ],
                'FtPqrCalificacion' => [
                    'model' => FtPqrCalificacion::class,
                    'attribute' => 'ft_pqr_respuesta',
                    'primary' => 'idft_pqr_respuesta',
                    'relation' => self::BELONGS_TO_MANY
                ]
            ]
        ];
    }

    /**
     * Posterior al adicionar valido los emails
     *
     * @return void
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function afterAdd()
    {
        $this->validEmails();
    }

    /**
     * Posterior al editar valido los emails
     *
     * @return void
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function afterEdit()
    {
        $this->validEmails();
    }

    /**
     * Verifica si los email son validos
     *
     * @return void
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function validEmails()
    {
        if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("El email ({$this->email}) NO es valido");
        }

        if ($this->email_copia) {
            $emails = explode(",", $this->email_copia);
            foreach ($emails as $copia) {
                if (!filter_var($copia, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception("El email ({$copia}) NO es valido");
                }
            }
        }
    }

    /**
     * Carga el mostrar de la respuesta a la PQR
     *
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function showTemplate(): string
    {
        return $this->content;
    }

    /**
     * Seteo la funcion principal y devuelvo solo
     * los parametros necesarios al editar
     *
     * @return void
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function getRouteParams(string $scope): array
    {
        $data = [];
        if ($scope == self::SCOPE_ROUTE_PARAMS_EDIT) {
            $data = [
                'numero' => (int) $this->Documento->numero
            ];
        }
        return $data;
    }

    public function afterRad(): void
    {
        $this->notifyEmail();
    }

    public function notifyEmail()
    {
        $DocumentoPqr = $this->FtPqr->Documento;
        $message = "Cordial Saludo,<br/><br/>Adjunto encontrara la respuesta a la solicitud de PQR con número de radicado {$DocumentoPqr->numero}.<br/><br/>";

        $SendMailController = new SendMailController(
            "Respuesta solicitud de PQR # {$DocumentoPqr->numero}",
            $message
        );

        $SendMailController->setDestinations(
            SendMailController::DESTINATION_TYPE_EMAIL,
            [$this->email]
        );

        if ($this->email_copia) {
            $SendMailController->setCopyDestinations(
                SendMailController::DESTINATION_TYPE_EMAIL,
                explode(",", $this->email_copia)
            );
        }

        if (!$this->Documento->pdf) {
            $DocumentPdfGenerator = new DocumentPdfGenerator($this->Documento);
            $route = $DocumentPdfGenerator->refreshFile();

            if (!$route) {
                $log = [
                    'error' => "MpdfController NO genero el PDF, iddoc: {$this->Documento->getPK()}",
                    'message' => "No fue posible generar el PDF para el formato Respuesta PQR"
                ];
                UtilitiesPqr::notifyAdministrator(
                    "No fue posible generar el PDF para la Respueta a la PQR # {$DocumentoPqr->numero}",
                    $log
                );
            } else {
                $SendMailController->setAttachments(
                    $SendMailController::ATTACHMENT_TYPE_JSON,
                    [$this->Documento->pdf]
                );
            }
        }

        if ($records = $this->Documento->Anexos) {
            $anexos = [];
            foreach ($records as $Anexo) {
                $anexos[] = $Anexo->ruta;
            }
            $SendMailController->setAttachments(
                $SendMailController::ATTACHMENT_TYPE_JSON,
                $anexos,
                true
            );
        }

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
        } else {

            $params = CryptController::encrypt(json_encode([
                'ft_pqr_respuesta' => $this->getPK(),
                'anterior' => $this->Documento->getPK()
            ]));
            $url = ABSOLUTE_SAIA_ROUTE . WebserviceCalificacion::DIRECTORY . "/index.html?d={$params}";
            $message = "Cordial Saludo,<br/><br/>
            Nos gustaría recibir tus comentarios sobre el servicio que has recibido por parte de nuestro equipo.<br/><a href='{$url}'>Calificar el servicio</a>";

            $SendMailController = new SendMailController(
                "Queremos conocer tu opinión! (Solicitud de PQR # {$DocumentoPqr->numero})",
                $message
            );

            $SendMailController->setDestinations(
                SendMailController::DESTINATION_TYPE_EMAIL,
                [$this->email]
            );

            $send = $SendMailController->send();
            if ($send !== true) {
                $log = [
                    'error' => $send,
                    'message' => "No fue posible enviar la calificacion a la PQR # {$DocumentoPqr->numero}"
                ];
                UtilitiesPqr::notifyAdministrator(
                    "No fue posible enviar la calificacion a la PQR # {$DocumentoPqr->numero}",
                    $log
                );
            }
        }

        return true;
    }

    public function getFtPqrCalificacion(): ?FtPqrCalificacion
    {
        if (!$this->PqrCalificacion) {
            foreach ($this->FtPqrCalificacion as $FtPqrCalificacion) {
                if (!$FtPqrCalificacion->Documento->isActive()) {
                    $this->PqrCalificacion = $FtPqrCalificacion;
                    break;
                }
            }
        }
        return $this->PqrCalificacion;
    }
}
