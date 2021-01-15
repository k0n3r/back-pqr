<?php

namespace App\Bundles\pqr\formatos\pqr_respuesta;

use Saia\models\Tercero;
use Saia\models\BuzonSalida;
use Saia\models\Funcionario;
use Saia\models\anexos\Anexos;
use Saia\controllers\DateController;
use Saia\controllers\anexos\FileJson;
use Saia\controllers\CryptController;
use Saia\models\localidades\Municipio;
use App\Bundles\pqr\formatos\pqr\FtPqr;
use Saia\controllers\SessionController;
use Saia\models\formatos\CamposFormato;
use Saia\controllers\documento\Transfer;
use Saia\controllers\SendMailController;
use App\Bundles\pqr\helpers\UtilitiesPqr;
use Saia\controllers\DistributionService;
use App\Bundles\pqr\Services\models\PqrForm;
use App\Bundles\pqr\Services\PqrFormService;
use Saia\controllers\functions\CoreFunctions;
use App\Bundles\pqr\Services\models\PqrHistory;
use App\Bundles\pqr\Services\models\PqrNotyMessage;
use App\Bundles\pqr\Services\PqrNotyMessageService;
use App\Bundles\pqr\formatos\pqr_calificacion\FtPqrCalificacion;

class FtPqrRespuesta extends FtPqrRespuestaProperties
{
    const ATENTAMENTE_DESPEDIDA = 1;
    const CORDIALMENTE_DESPEDIDA = 2;
    const OTRA_DESPEDIDA = 3;

    const DISTRIBUCION_RECOGIDA_ENTREGA = 1;
    const DISTRIBUCION_SOLO_ENTREGA = 2;
    const DISTRIBUCION_NO_REQUIERE_MENSAJERIA = 3;
    const DISTRIBUCION_ENVIAR_EMAIL = 4;

    /**
     * @var PqrForm
     */
    private PqrForm $PqrForm;

    /**
     * @var Funcionario
     */
    private Funcionario $Funcionario;

    public function __construct($id = null)
    {
        parent::__construct($id);

        if (!$this->PqrForm = PqrForm::getPqrFormActive()) {
            throw new \Exception("No se encuentra el formulario activo", 200);
        }
        $this->Funcionario = SessionController::getUser();
    }

    /**
     * @inheritDoc
     */
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
                ],
                'Municipio' => [
                    'model' => Municipio::class,
                    'attribute' => 'idmunicipio',
                    'primary' => 'ciudad_origen',
                    'relation' => self::BELONGS_TO_ONE
                ],
                'Tercero' => [
                    'model' => Tercero::class,
                    'attribute' => 'idtercero',
                    'primary' => 'destino',
                    'relation' => self::BELONGS_TO_ONE
                ],
            ]
        ];
    }

    /**
     * @inheritDoc
     */
    public function afterAdd(): bool
    {
        if ($this->sendByEmail()) {
            $this->validEmails();
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function afterEdit(): bool
    {
        if ($this->sendByEmail()) {
            $this->validEmails();
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function afterRad(): bool
    {
        $description = "Se genera la respuesta con radicado # {$this->Documento->numero}";
        $tipo = PqrHistory::TIPO_RESPUESTA;

        return $this->saveHistory($description, $tipo) &&
            $this->transferCopiaInterna() &&
            $this->saveDistribution() &&
            $this->notifyEmail();
    }

    /**
     * Add
     * 
     * Genera el HTML para seleccionar la ciudad de origen
     *
     * @param integer $idCamposFormato
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function selectCity(int $idCamposFormato): string
    {
        $CamposFormato = new CamposFormato($idCamposFormato);

        return <<<HTML
        <div class='form-group form-group-default form-group-default-select2 required' id='group_{$CamposFormato->nombre}'>
            <label title='Ciudad origen' class='autocomplete'>{$CamposFormato->etiqueta}</label>
            <select class="full-width required" id='ciudad_origen' name='{$CamposFormato->nombre}'></select>
        </div>
HTML;
    }

    /**
     * Add
     * 
     * Genera el HTML para checkear la solicitud de encuesta
     *
     * @param integer $idCamposFormato
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function fieldSatisfactionSurvey(int $idCamposFormato): string
    {
        $CamposFormato = new CamposFormato($idCamposFormato);

        return "<div class='form-group form-group-default' id='group_sol_encuesta'>
            <div class='checkbox check-success input-group'>
                <input type='checkbox' name='sol_encuesta' id='sol_encuesta' value='1'>
                <label for='sol_encuesta' class='mr-3'>
                    {$CamposFormato->etiqueta}
                </label>
            </div>
        </div>";
    }


    /**
     * Show
     * 
     * Carga el mostrar de la respuesta a la PQR
     *
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function showTemplate(): string
    {
        $Qr = CoreFunctions::mostrar_qr($this);
        $firmas = CoreFunctions::mostrar_estado_proceso($this);

        $code = <<<HTML
            <table border="0" cellspacing="0" style="width: 100%;">
                <tbody>
                    <tr>
                        <td colspan="2">{$this->getFechaCiudad()}</td>
                    </tr>

                    <tr>
                        <td colspan="2">&nbsp;</td>
                    </tr>
                    <tr>
                        <td colspan="2">&nbsp;</td>
                    </tr>
                    <tr>
                        <td colspan="2">&nbsp;</td>
                    </tr>

                    <tr>
                        <td>{$this->getInfoDestino()}</td>
                        <td style="text-align:center">{$Qr}<br/>No.{$this->getRadicado()}</td>
                    </tr>

                    <tr>
                        <td colspan="2">&nbsp;</td>
                    </tr>
                    <tr>
                        <td colspan="2">&nbsp;</td>
                    </tr>

                    <tr>
                        <td colspan="2">ASUNTO: {$this->asunto}</td>
                    </tr>

                    <tr>
                        <td colspan="2">&nbsp;</td>
                    </tr>
                    <tr>
                        <td colspan="2">&nbsp;</td>
                    </tr>

                    <tr>
                        <td colspan="2">Cordial saludo:</td>
                    </tr>
                </tbody>
            </table>
            {$this->contenido}
            <p>{$this->getDespedida()}<br/><br/></p>
            {$firmas}
            <p>{$this->getOtherData()}</p>
HTML;

        return $code;
    }

    /**
     * Obtiene el radicado
     *
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    private function getRadicado(): string
    {
        return DateController::convertDate($this->Documento->fecha, 'Ymd')
            . " - " . $this->Documento->numero;
    }

    /**
     * Obtiene fecha y nombre de la ciudad
     *
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    private function getFechaCiudad(): string
    {
        return $this->Municipio->nombre . ", " . strftime("%d de %B de %Y", strtotime($this->Documento->fecha));
    }

    /**
     * Obtiene los datos del remitente
     *
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    private function getInfoDestino(): string
    {
        $code = '';
        if ($this->Tercero) {
            $code .= $this->Tercero->titulo ? $this->Tercero->titulo . "<br/>" : '';
            $code .= $this->Tercero->nombre . "<br/>";
            $code .= $this->Tercero->cargo ? $this->Tercero->cargo . "<br/>" : '';
            $code .= $this->Tercero->direccion ? $this->Tercero->direccion . "<br/>" : '';
            $code .= $this->Tercero->telefono ? $this->Tercero->telefono . "<br/>" : '';
        }

        return $code;
    }

    /**
     * Obtiene el texto de despedida
     *
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    private function getDespedida(): string
    {
        return $this->getKeyField('despedida') == self::OTRA_DESPEDIDA ?
            $this->otra_despedida : $this->getFieldValue('despedida');
    }

    /**
     * Obtiene mas informacion que va en el mostrar
     *
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    private function getOtherData(): string
    {
        $data = '';
        if ($this->anexos_fisicos) {
            $data .= "Anexos físicos: {$this->anexos_fisicos}<br/>";
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
     * @date 2020
     */
    private function getNameAnexosDigitales(): string
    {
        $id = $this->Formato->getField('anexos_digitales')->getPK();

        $names = Anexos::findColumn('etiqueta', [
            'documento_iddocumento' => $this->Documento->getPK(),
            'campos_formato' => $id
        ]);

        return $names ? implode(', ', $names) : '';
    }

    /**
     * Obtiene los nombres de las personas a quien va con copia externa
     *
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    private function getNameCopiaExterna(): string
    {
        if (!$this->copia) {
            return '';
        }

        $names = [];
        $records = explode(',', $this->copia);
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
     * @date 2020
     */
    private function getCreador(): string
    {
        return $this->Documento->Funcionario->getName();
    }

    /**
     * Verifica si los email son validos
     *
     * @return void
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     * 
     * @throws Exception
     */
    private function validEmails(): void
    {
        $email = $this->Tercero->correo;
        if (!$email) {
            throw new \Exception("Debe ingresar el email (Destino)");
        }

        if (!UtilitiesPqr::isEmailValid($email)) {
            throw new \Exception("El email ({$email}) NO es valido");
        }

        if ($emailCopy = $this->getCopyEmail()) {
            foreach ($emailCopy as $copia) {
                if (!$copia) {
                    throw new \Exception("Debe ingresar el email (Con copia a)");
                }

                if (!UtilitiesPqr::isEmailValid($copia)) {
                    throw new \Exception("El email en copia externa ({$copia}) NO es valido");
                }
            }
        }
    }

    /**
     * Seteo la funcion principal y devuelvo solo
     * los parametros necesarios al editar
     *
     * @return array
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
     * Se crea un registro en el historial
     *
     * @param string $description
     * @param integer $type
     * @return boolean
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    private function saveHistory(string $description, int $type): bool
    {
        $history = [
            'fecha' => date('Y-m-d H:i:s'),
            'idft' => $this->FtPqr->getPK(),
            'fk_funcionario' => $this->Funcionario->getPK(),
            'tipo' => $type,
            'idfk' => $this->getPK(),
            'descripcion' => $description
        ];

        if (!PqrHistory::newRecord($history)) {
            throw new \Exception("No fue posible guardar el historial", 200);
        }

        return true;
    }

    /**
     * Registra la distribucion
     *
     * @return boolean
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    private function saveDistribution(): bool
    {
        switch ((int) $this->getKeyField('tipo_distribucion')) {
            case self::DISTRIBUCION_RECOGIDA_ENTREGA:
                $recogida = DistributionService::ESTADO_RECOGIDA;
                $estado = DistributionService::DISTRIBUCION_POR_RECEPCIONAR;
                break;

            case self::DISTRIBUCION_SOLO_ENTREGA:
                $recogida = DistributionService::ESTADO_ENTREGA;
                $estado = DistributionService::DISTRIBUCION_PENDIENTE;
                break;

            case self::DISTRIBUCION_NO_REQUIERE_MENSAJERIA:
            case self::DISTRIBUCION_ENVIAR_EMAIL:
                $recogida = DistributionService::ESTADO_ENTREGA;
                $estado = DistributionService::DISTRIBUCION_FINALIZADA;
                break;

            default:
                throw new \Exception("Tipo de distribucion no definida", 200);
                break;
        }
        $DistributionService = new DistributionService($this->Documento);
        $DistributionService->start(
            $this->dependencia,
            DistributionService::TIPO_INTERNO,
            $this->destino,
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
     * @date 2020
     */
    private function sendByEmail(): bool
    {
        return (int) $this->getKeyField('tipo_distribucion') == self::DISTRIBUCION_ENVIAR_EMAIL;
    }

    /**
     * Transfiere a los ingresados
     * en copia interna
     *
     * @return boolean
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    private function transferCopiaInterna(): bool
    {
        if ($this->copia_interna) {
            $Transfer = new Transfer(
                $this->Documento,
                $this->Funcionario->funcionario_codigo,
                BuzonSalida::NOMBRE_COPIA
            );
            $destinations = explode(',', $this->copia_interna);
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
     * @date 2020
     */
    private function notifyEmail(): bool
    {
        if (!$this->sendByEmail()) {
            return true;
        }

        $DocumentoPqr = $this->FtPqr->Documento;
        $message = "Cordial Saludo,<br/><br/>Adjunto encontrara la respuesta a la solicitud de {$this->PqrForm->label} con número de radicado {$DocumentoPqr->numero}.<br/><br/>";
        $subject = "Respuesta solicitud de {$this->PqrForm->label} # {$DocumentoPqr->numero}";

        if ($PqrNotyMessage = PqrNotyMessage::findByAttributes([
            'name' => 'f2_email_respuesta'
        ])) {
            $message = PqrNotyMessageService::resolveVariables($PqrNotyMessage->message_body, $this->FtPqr);
            $subject = PqrNotyMessageService::resolveVariables($PqrNotyMessage->subject, $this->FtPqr);
        }

        if ($this->sol_encuesta) {
            $url = $this->getUrlEncuesta();
            $message .= "Califica nuestro servicio haciendo clic en el siguiente enlace: <a href='{$url}'>Calificar el servicio</a> .<br/><br/>";
        }

        $SendMailController = new SendMailController(
            $subject,
            $message
        );

        $SendMailController->setDestinations(
            SendMailController::DESTINATION_TYPE_EMAIL,
            [$this->Tercero->correo]
        );

        if ($emailCopy = $this->getCopyEmail()) {
            $SendMailController->setCopyDestinations(
                SendMailController::DESTINATION_TYPE_EMAIL,
                $emailCopy
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

            throw new \Exception("No fue posible notificar la respuesta", 200);
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
     * @date 2020
     */
    private function getCopyEmail(): array
    {
        $emails = [];
        if ($this->copia) {
            $records = explode(',', $this->copia);
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
     * @date 2020
     */
    private function getUrlEncuesta(): string
    {
        $params = CryptController::encrypt(json_encode([
            'ft_pqr_respuesta' => $this->getPK(),
            'anterior' => $this->Documento->getPK()
        ]));

        return PqrFormService::URLWSCALIFICACION . "?d={$params}";
    }

    /**
     * Obtiene la Calificaciones
     * Utilizado en reporteFunciones.php
     * 
     * @return FtPqrCalificacion[]
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function getFtPqrCalificacion(): array
    {
        $data = [];
        if ($this->FtPqrCalificacion) {
            foreach ($this->FtPqrCalificacion as $FtPqrCalificacion) {
                if (!$FtPqrCalificacion->Documento->isActive()) {
                    $data[] = $FtPqrCalificacion;
                }
            }
        }
        return $data;
    }

    /**
     * Solicita via email la encuesta de satisfaccion
     *
     * @return boolean
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     * 
     * @throws Exception
     */
    public function requestSurvey(): bool
    {
        $email = $this->Tercero->correo;
        if (!UtilitiesPqr::isEmailValid($email)) {
            throw new \Exception("El email ({$email}) NO es valido");
        }

        $nameFormat = $this->getFormat()->etiqueta;
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
            [$email]
        );

        $send = $SendMailController->send();
        if ($send !== true) {
            $message = "No fue posible solicitar la calificacion de la ({$nameFormat}) # {$this->Documento->numero}";
            $log = [
                'error' => $send,
                'message' => $message
            ];

            UtilitiesPqr::notifyAdministrator(
                $message,
                $log
            );
            throw new \Exception($message);
        }

        $description = "Se solicita la calificación de la ({$nameFormat}) # {$this->Documento->numero} al e-mail: ({$email})";
        $tipo = PqrHistory::TIPO_CALIFICACION;

        return $this->saveHistory($description, $tipo);
    }
}
