<?php

namespace Saia\Pqr\formatos\pqr_respuesta;

use Exception;
use Saia\Pqr\models\PqrForm;
use Saia\Pqr\models\PqrHistory;
use Saia\Pqr\formatos\pqr\FtPqr;
use Saia\Pqr\helpers\UtilitiesPqr;
use Saia\controllers\anexos\FileJson;
use Saia\controllers\CryptController;
use Saia\controllers\SendMailController;
use Saia\controllers\SessionController;
use Saia\models\formatos\CamposFormato;
use Saia\Pqr\formatos\pqr_calificacion\FtPqrCalificacion;

class FtPqrRespuesta extends FtPqrRespuestaProperties
{

    private PqrForm $PqrForm;

    public function __construct($id = null)
    {
        parent::__construct($id);
        if (!$this->PqrForm = PqrForm::getPqrFormActive()) {
            throw new Exception("No se encuentra el formulario activo", 200);
        }
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
     * @inheritDoc
     */
    public function afterAdd(): bool
    {
        $this->validEmails();

        return true;
    }

    /**
     * @inheritDoc
     */
    public function afterEdit(): bool
    {
        $this->validEmails();

        return true;
    }


    public function fieldSatisfactionSurvey($idCamposFormato)
    {
        $CamposFormato = new CamposFormato($idCamposFormato);

        return "<div class='form-group form-group-default' id='group_{$CamposFormato->nombre}'>
            <div class='checkbox check-success input-group'>
                <input type='checkbox' name='{$CamposFormato->nombre}' id='{$CamposFormato->nombre}' value='1'>
                <label for='{$CamposFormato->nombre}' class='mr-3'>
                    {$CamposFormato->etiqueta}
                </label>
            </div>
        </div>";
    }

    /**
     * Verifica si los email son validos
     *
     * @return void
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    private function validEmails(): void
    {
        if ($this->email) {

            if (!UtilitiesPqr::isEmailValid($this->email)) {
                throw new Exception("El email ({$this->email}) NO es valido");
            }
        }

        if ($this->email_copia) {
            $emails = explode(",", $this->email_copia);
            foreach ($emails as $copia) {
                if (!UtilitiesPqr::isEmailValid($copia)) {
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

    /**
     * @inheritDoc
     */
    public function afterRad(): bool
    {
        $this->Documento->getPdfJson(true);
        $this->saveHistory();

        return $this->notifyEmail();
    }

    private function saveHistory(): bool
    {
        $history = [
            'fecha' => date('Y-m-d H:i:s'),
            'idft' => $this->FtPqr->getPK(),
            'nombre_funcionario' => SessionController::getUser()->getName(),
            'descripcion' => "Se crea la respuesta # {$this->Documento->numero}"
        ];
        if (!PqrHistory::newRecord($history)) {
            throw new \Exception("No fue posible guardar el historial del cambio", 200);
        }
        return true;
    }

    /**
     * Notifica la respuesta via Email
     *
     * @return boolean
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    private function notifyEmail(): bool
    {
        if (!$this->email && !$this->email_copia) {
            return true;
        }

        $DocumentoPqr = $this->FtPqr->Documento;
        $message = "Cordial Saludo,<br/><br/>Adjunto encontrara la respuesta a la solicitud de {$this->PqrForm->label} con número de radicado {$DocumentoPqr->numero}.<br/><br/>";

        if ($this->sol_encuesta) {
            //Este correo le llega a todos los que estan en copia
            $url = $this->getUrlEncuesta();
            $message = "Califica nuestro servicio haciendo clic en el siguiente enlace: <a href='{$url}'>Calificar el servicio</a> .<br/><br/>";
        }

        $SendMailController = new SendMailController(
            "Respuesta solicitud de {$this->PqrForm->label} # {$DocumentoPqr->numero}",
            $message
        );

        if ($this->email) {
            $SendMailController->setDestinations(
                SendMailController::DESTINATION_TYPE_EMAIL,
                [$this->email]
            );
        }

        if ($this->email_copia) {
            $SendMailController->setCopyDestinations(
                SendMailController::DESTINATION_TYPE_EMAIL,
                explode(",", $this->email_copia)
            );
        }

        $anexos = [];
        $File = new FileJson($this->Documento->getPdfJson());
        $anexos[] = $File;

        if ($records = $this->Documento->Anexos) {
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
        }

        return true;
    }

    /**
     * Obtiene la URL del ws para calificar el servicio o encuesta
     *
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    private function getUrlEncuesta(): string
    {
        $params = CryptController::encrypt(json_encode([
            'ft_pqr_respuesta' => $this->getPK(),
            'anterior' => $this->Documento->getPK()
        ]));
        //$url = ABSOLUTE_SAIA_ROUTE . WebserviceCalificacion::DIRECTORY . "/index.html?d={$params}";
        $url = "";

        return $url;
    }

    /**
     * Solicita via email la encuesta de satisfaccion
     *
     * @return boolean
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function requestSurvey(): bool
    {
        if (!$this->email) {
            return false;
        }

        $DocumentoPqr = $this->FtPqr->Documento;

        $url = $this->getUrlEncuesta();
        $message = "Cordial Saludo,<br/><br/>
        Nos gustaría recibir tus comentarios sobre el servicio que has recibido por parte de nuestro equipo.<br/><a href='{$url}'>Calificar el servicio</a>";

        $SendMailController = new SendMailController(
            "Queremos conocer tu opinión! (Solicitud de {$this->PqrForm->label} # {$DocumentoPqr->numero})",
            $message
        );

        $SendMailController->setDestinations(
            SendMailController::DESTINATION_TYPE_EMAIL,
            [$this->email]
        );

        return $SendMailController->send();
    }

    /**
     * Obtiene la Calificacion
     *
     * @return FtPqrCalificacion|null
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
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
