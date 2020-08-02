<?php

namespace Saia\Pqr\formatos\pqr_respuesta;

use Saia\models\Tercero;
use Saia\models\BuzonSalida;
use Saia\Pqr\models\PqrForm;
use Saia\models\Distribucion;
use Saia\models\anexos\Anexos;
use Saia\Pqr\models\PqrHistory;
use Saia\Pqr\formatos\pqr\FtPqr;
use Saia\Pqr\helpers\UtilitiesPqr;
use Saia\controllers\DateController;
use Saia\controllers\anexos\FileJson;
use Saia\controllers\CryptController;
use Saia\models\localidades\Municipio;
use Saia\controllers\SessionController;
use Saia\models\formatos\CamposFormato;
use Saia\controllers\documento\Transfer;
use Saia\controllers\SendMailController;
use Saia\Pqr\controllers\PqrFormController;
use Saia\controllers\DistribucionController;
use Saia\controllers\functions\CoreFunctions;
use Saia\Pqr\formatos\pqr_calificacion\FtPqrCalificacion;

class FtPqrRespuesta extends FtPqrRespuestaProperties
{
    const ATENTAMENTE_DESPEDIDA = 1;
    const CORDIALMENTE_DESPEDIDA = 2;
    const OTRA_DESPEDIDA = 3;

    private PqrForm $PqrForm;

    public function __construct($id = null)
    {
        parent::__construct($id);

        if (!$this->PqrForm = PqrForm::getPqrFormActive()) {
            throw new \Exception("No se encuentra el formulario activo", 200);
        }
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
        // $this->validEmails();

        return true;
    }

    /**
     * @inheritDoc
     */
    public function afterEdit(): bool
    {
        // $this->validEmails();

        return true;
    }

    /**
     * @inheritDoc
     */
    public function afterRad(): bool
    {
        return $this->saveHistory() &&
            $this->saveDistribution() &&
            $this->transferCopiaInterna() &&
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
                        <td colspan="2">{$this->asunto}</td>
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

        $data .= "Proyectó: {$this->getProyecto()}";

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
     * Obtiene el nombre del creador o de quien proyecto
     *
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    private function getProyecto(): string
    {
        return $this->Documento->Funcionario->getName();
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
                throw new \Exception("El email ({$this->email}) NO es valido");
            }
        }

        if ($this->email_copia) {
            $emails = explode(",", $this->email_copia);
            foreach ($emails as $copia) {
                if (!UtilitiesPqr::isEmailValid($copia)) {
                    throw new \Exception("El email ({$copia}) NO es valido");
                }
            }
        }
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
     * Crea un registro de historial de Pqr
     *
     * @return boolean
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
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

    private function saveDistribution(): bool
    {
        switch ((int) $this->getKeyField('tipo_distribucion')) {
            case 1:
                $recogida = Distribucion::REQUIRE_RECOGIDA;
                $estado = Distribucion::ESTADO_POR_RECEPCIONAR;
                break;

            case 2:
                $recogida = Distribucion::NO_REQUIRE_RECOGIDA;
                $estado = Distribucion::ESTADO_POR_DISTRIBUIR;
                break;

            case 3:
                $recogida = Distribucion::NO_REQUIRE_RECOGIDA;
                $estado = Distribucion::ESTADO_FINALIZADO;
                break;
            default:
                throw new \Exception("Tipo de distribucion no definida", 200);
                break;
        }

        DistribucionController::startDistribution(
            $this,
            'dependencia',
            Distribucion::ORIGEN_INTERNO,
            'destino',
            Distribucion::DESTINO_EXTERNO,
            $estado,
            $recogida
        );

        return true;
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
                SessionController::getValue('funcionario_codigo'),
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
        if (!$this->email && !$this->email_copia) {
            return true;
        }

        $DocumentoPqr = $this->FtPqr->Documento;
        $message = "Cordial Saludo,<br/><br/>Adjunto encontrara la respuesta a la solicitud de {$this->PqrForm->label} con número de radicado {$DocumentoPqr->numero}.<br/><br/>";

        if ($this->sol_encuesta) {
            $url = $this->getUrlEncuesta();
            $message .= "Califica nuestro servicio haciendo clic en el siguiente enlace: <a href='{$url}'>Calificar el servicio</a> .<br/><br/>";
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

        return PqrFormController::URLWSCALIFICACION . "index.html?d={$params}";
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
     * Obtiene la Calificaciones
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
}
