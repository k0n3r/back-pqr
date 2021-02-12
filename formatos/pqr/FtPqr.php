<?php

namespace App\Bundles\pqr\formatos\pqr;

use Saia\models\Tercero;
use Saia\models\BuzonSalida;
use Saia\models\Funcionario;
use Saia\controllers\DateController;
use Saia\controllers\TerceroService;
use Saia\controllers\anexos\FileJson;
use Saia\controllers\CryptController;
use Saia\controllers\SessionController;
use Saia\controllers\documento\Transfer;
use Saia\controllers\SendMailController;
use App\Bundles\pqr\helpers\UtilitiesPqr;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use App\Bundles\pqr\Services\FtPqrService;
use App\Bundles\pqr\Services\models\PqrForm;
use App\Bundles\pqr\Services\models\PqrBackup;
use App\Bundles\pqr\Services\models\PqrHistory;
use App\Bundles\pqr\Services\models\PqrFormField;
use App\Bundles\pqr\Services\models\PqrNotyMessage;
use App\Bundles\pqr\Services\PqrNotyMessageService;
use App\Bundles\pqr\formatos\pqr_respuesta\FtPqrRespuesta;

class FtPqr extends FtPqrProperties
{
    const ESTADO_PENDIENTE = 'PENDIENTE';
    const ESTADO_PROCESO = 'PROCESO';
    const ESTADO_TERMINADO = 'TERMINADO';

    const VENCIMIENTO_ROJO = 1; //DIAS
    const VENCIMIENTO_AMARILLO = 5; //DIAS

    public PqrForm $PqrForm;
    private Funcionario $Funcionario;

    public function __construct($id = null)
    {
        parent::__construct($id);
        if (!$this->PqrForm = PqrForm::getPqrFormActive()) {
            throw new \Exception("No se encuentra el formulario activo", 200);
        }
        $this->Funcionario = SessionController::getUser();

        $PqrFormField = $this->PqrForm->getRow('sys_subtipo');

        $this->addRouteParams([
            'sys_subtipo' => (int) $PqrFormField->active
        ]);
    }

    /**
     * more Attributes
     *
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    protected function defineMoreAttributes(): array
    {
        return [
            'relations' => [
                'PqrBackup' => [
                    'model' => PqrBackup::class,
                    'attribute' => 'fk_documento',
                    'primary' => 'documento_iddocumento',
                    'relation' => self::BELONGS_TO_ONE
                ],
                'PqrRespuesta' => [
                    'model' => FtPqrRespuesta::class,
                    'attribute' => 'ft_pqr',
                    'primary' => 'idft_pqr',
                    'relation' => self::BELONGS_TO_MANY
                ],
                'Tercero' => [
                    'model' => Tercero::class,
                    'attribute' => 'idtercero',
                    'primary' => 'sys_tercero',
                    'relation' => self::BELONGS_TO_ONE
                ]
            ]
        ];
    }

    /**
     * Retorna el Servicio
     *
     * @return FtPqrService
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2021
     */
    public function getService(): FtPqrService
    {
        return new FtPqrService($this);
    }

    /**
     *@inheritDoc
     */
    public function afterAdd(): bool
    {
        return $this->validSysEmail();
    }

    /**
     *@inheritDoc
     */
    public function afterEdit(): bool
    {
        return $this->validSysEmail();
    }

    /**
     *@inheritDoc
     */
    public function beforeRad(): bool
    {
        return $this->createBackup() &&
            $this->updateFechaVencimiento() &&
            $this->createTercero();
    }

    /**
     *@inheritDoc
     */
    public function afterRad(): bool
    {

        $message = "<br/>Su solicitud ha sido generada con el número de radicado <strong>{$this->Documento->numero}</strong><br/>el seguimiento lo puede realizar en el apartado de consulta con el radicado asignado<br/><br/>Gracias por visitarnos!";

        if ($PqrNotyMessage = PqrNotyMessage::findByAttributes([
            'name' => 'ws_noty_radicado'
        ])) {
            $message = PqrNotyMessageService::resolveVariables($PqrNotyMessage->message_body, $this);
        }

        $this->addTemporaryParameters([
            'message' => $message
        ]);
        return $this->sendNotifications() && $this->notifyEmail();
    }

    /**
     * Carga todo el mostrar del formulario
     *
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function showContent(): string
    {
        $data = $this->PqrBackup->getDataJson();
        $Qr = UtilitiesPqr::showQr($this);

        $fecha = DateController::convertDate($this->Documento->fecha, 'Ymd');
        $text = sprintf(
            '%s %s-%s',
            'Radicado:',
            $fecha,
            $this->Documento->numero
        );
        $labelPQR = strtoupper($this->PqrForm->label);
        $code = '<table class="table table-borderless" style="width:100%">';
        $code .= '<tr>
            <td style="width:50%;">
                <p>Hemos recibido su ' . $labelPQR . '.<br/><br/>
                    Puede hacer seguimiento en la opción CONSULTAR MI ' . $labelPQR . ' de nuestro sitio Web.
                </p>
            </td>
            <td style="width:50%;text-align:center">' . $Qr  . '<br/>' . $text . ' </td>
        </tr>
        <tr><td colspan="2">&nbsp;</td></tr>';
        foreach ($data as $key => $value) {
            $code .= '<tr>
                <td style="width:50%"><strong>' . $key . '</strong></td>
                <td style="width:50%">' . $value . '</td>
            </tr>';
        }
        $code .= '</table>';

        return $code;
    }

    /**
     * Obtiene Instancias de las respuestas a la PQR
     *
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function getPqrAnswers(): array
    {
        $data = [];
        foreach ($this->PqrRespuesta as $FtPqrRespuesta) {
            if (!$FtPqrRespuesta->Documento->isActive()) {
                $data[] = $FtPqrRespuesta;
            }
        }
        return $data;
    }

    /**
     * Carga el HTML del adicionar/editar para los campos
     *  AutompleteD
     *
     * @param integer $idCamposFormato
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function autocompleteD(int $idCamposFormato): string
    {
        $PqrFormField = PqrFormField::findByAttributes([
            'fk_campos_formato' => $idCamposFormato
        ]);
        return $this->generateField($PqrFormField);
    }

    /**
     * Carga el HTML del adicionar/editar para los campos
     *  AutompleteMw
     *
     * @param integer $idCamposFormato
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function autocompleteM(int $idCamposFormato): string
    {
        $PqrFormField = PqrFormField::findByAttributes([
            'fk_campos_formato' => $idCamposFormato
        ]);

        return $this->generateField($PqrFormField);
    }

    /**
     * Obtiene la fecha de vencimiento con el color que identifica
     * el tiempo pendiente por responder la PQR
     *
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function getColorExpiration(): string
    {
        if (!$this->sys_fecha_vencimiento) {
            return 'Fecha vencimiento no configurada';
        }

        $now = $this->sys_fecha_terminado ? new \DateTime($this->sys_fecha_terminado) : new \DateTime();
        $diff = $now->diff(new \DateTime($this->sys_fecha_vencimiento));

        $color = "success";
        if ($diff->invert || $diff->days <= self::VENCIMIENTO_ROJO) {
            $color = 'danger';
        } else if ($diff->days <= self::VENCIMIENTO_AMARILLO) {
            $color = 'warning';
        }

        $date = DateController::convertDate(
            $this->sys_fecha_vencimiento,
            DateController::PUBLIC_DATE_FORMAT
        );

        return "<span class='badge badge-{$color}'>{$date}</span>";
    }

    /**
     * Muestra la fecha finalizacion
     *
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function getEndDate(): string
    {
        if (!$this->sys_fecha_terminado) {
            return 'Fecha fin no configurada';
        }

        return DateController::convertDate(
            $this->sys_fecha_terminado,
            DateController::PUBLIC_DATE_FORMAT
        );
    }

    /**
     * Muestra los dias de retraso al solucionar la pqr
     *
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function getDaysLate(): string
    {
        if (!$this->sys_fecha_vencimiento) {
            return 'Fecha vencimiento no configurada';
        }

        if (!$this->sys_fecha_terminado) {
            return 'Fecha fin no configurada';
        }

        $now = new \DateTime($this->sys_fecha_terminado);
        $diff = $now->diff(new \DateTime($this->sys_fecha_vencimiento));

        $dias = 0;
        if ($diff->invert) {
            $dias = "<span class='badge badge-danger'>{$diff->days}</span>";
        }

        return $dias;
    }

    /**
     * Muestra los dias transcurridos desde la radicacion hasta la fecha actual
     *
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function getDaysWait(): string
    {
        $now = !$this->sys_fecha_terminado ? new \DateTime()
            : new \DateTime($this->sys_fecha_terminado);

        $diff = $now->diff(new \DateTime($this->Documento->fecha));

        return $diff->days;
    }

    /**
     * Obtiene el valor del campo que mostrara en el reporte
     *
     * @param string $name
     * @return string|null
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function getValueForReport(string $name): ?string
    {
        $value = $this->getValue($this->PqrForm->getRow($name));

        return $value ? implode(',', $value) : null;
    }

    /**
     * Genera el backup del formulario
     *
     * @return boolean
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    private function createBackup(): bool
    {
        if (!PqrBackup::newRecord([
            'fk_documento' => $this->documento_iddocumento,
            'fk_pqr' => $this->getPK(),
            'data_json' => json_encode($this->getDataRow())
        ])) {
            throw new \Exception("No fue posible registrar el backup", 1);
        }
        return true;
    }

    /**
     * Obtiene las valores del modelo para guardarlos en el backup
     *
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    private function getDataRow(): array
    {
        $data = [];
        if ($this->PqrForm->show_anonymous) {
            $data = [
                'REGISTRADO COMO ANÓNIMO' => $this->sys_anonimo ? 'SI' : 'NO'
            ];
        }

        $Fields = $this->PqrForm->PqrFormFields;
        foreach ($Fields as  $PqrFormField) {
            if ($PqrFormField->active) {
                if ($value = $this->getValue($PqrFormField)) {
                    $data = array_merge($data, $value);
                }
            }
        }

        return $data;
    }

    /**
     * Obtiene el valor de un campo
     *
     * @param PqrFormField $PqrFormField
     * @return array|null
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    private function getValue(PqrFormField $PqrFormField): ?array
    {
        $PqrHtmlField = $PqrFormField->PqrHtmlField;
        $fieldName = $PqrFormField->name;
        $label = strtoupper($PqrFormField->label);

        switch ($PqrHtmlField->type_saia) {
            case 'Hidden':
            case 'Attached':
                continue;
                break;

            case 'Radio':
            case 'Checkbox':
            case 'Select':
                $data[$label] = $this->getFieldValue($fieldName);
                break;
            case 'AutocompleteD';
            case 'AutocompleteM';
                if ($this->$fieldName) {
                    $value = $PqrFormField->getService()->getListDataForAutocomplete(['id' => $this->$fieldName]);
                }
                $data[$label] = $value ? $value[0]['text'] : '';
                break;
            default:
                $data[$label] = $this->$fieldName;
                break;
        }

        return $data;
    }

    /**
     * Valida si el campo sys_email es valido
     *
     * @return boolean
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    private function validSysEmail(): bool
    {
        if ($this->sys_email) {
            if (!UtilitiesPqr::isEmailValid($this->sys_email)) {
                throw new \Exception("Esta dirección de correo ({$this->sys_email}) no es válida.", 200);
            }
        }
        return true;
    }

    /**
     * Actualiza la fecha de vencimiento
     *
     * @return boolean
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    private function updateFechaVencimiento(): bool
    {
        $fecha = $this->getDateForType();

        $oldDate = $this->sys_fecha_vencimiento;
        $this->sys_fecha_vencimiento = $fecha;
        $this->update();

        $this->Documento->fecha_limite = $fecha;
        $this->Documento->update();

        if ($oldDate != $this->sys_fecha_vencimiento) {
            $history = [
                'fecha' => date('Y-m-d H:i:s'),
                'idft' => $this->getPK(),
                'fk_funcionario' => $this->Funcionario->getPK(),
                'tipo' => PqrHistory::TIPO_CAMBIO_VENCIMIENTO,
                'idfk' => 0,
                'descripcion' => "Se actualiza la fecha de vencimiento a " .
                    DateController::convertDate(
                        $this->sys_fecha_vencimiento,
                        DateController::PUBLIC_DATE_FORMAT
                    )
            ];

            if (!PqrHistory::newRecord($history)) {
                throw new \Exception("No fue posible actualizar el historial", 200);
            }
        }

        return true;
    }

    /**
     * Retonar la fecha de vencimiento basado en la fecha de aprobacion 
     * y el tipo
     *
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function getDateForType(): string
    {
        $options = json_decode($this->PqrForm->getRow('sys_tipo')->CamposFormato->opciones);

        $dias = 1;
        foreach ($options as $option) {
            if ($option->idcampo_opciones == $this->sys_tipo) {
                $dias = $option->dias ?? 0;
                break;
            }
        }

        return (DateController::addBusinessDays(
            new \DateTime($this->Documento->fecha),
            $dias
        ))->format('Y-m-d H:i:s');
    }

    /**
     * Notifica al email registrado
     *
     * @return boolean
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    private function notifyEmail(): bool
    {
        if (!$this->sys_email) {
            return true;
        }

        $message = "Cordial Saludo,<br/><br/>Su solicitud ha sido generada con el número de radicado {$this->Documento->numero}, adjunto encontrará una copia de la {$this->PqrForm->label} diligenciada el día de hoy.<br/><br/>
        El seguimiento lo puede realizar escaneando el código QR o consultando con el número de radicado asignado";
        $subject = "Solicitud de {$this->PqrForm->label} # {$this->Documento->numero}";

        if ($PqrNotyMessage = PqrNotyMessage::findByAttributes([
            'name' => 'f1_email_solicitante'
        ])) {
            $message = PqrNotyMessageService::resolveVariables($PqrNotyMessage->message_body, $this);
            $subject = PqrNotyMessageService::resolveVariables($PqrNotyMessage->subject, $this);
        }

        $SendMailController = new SendMailController(
            $subject,
            $message
        );

        $SendMailController->setDestinations(
            SendMailController::DESTINATION_TYPE_EMAIL,
            [$this->sys_email]
        );

        $File = new FileJson($this->Documento->getPdfJson());
        $SendMailController->setAttachments([$File]);

        $send = $SendMailController->send();
        if ($send !== true) {
            $log = [
                'error' => $send,
                'message' => "No fue posible notificar la PQR # {$this->Documento->numero}"
            ];
            UtilitiesPqr::notifyAdministrator(
                "No fue posible notificar la PQR # {$this->Documento->numero}",
                $log
            );
        }

        return true;
    }

    /**
     * Html de los campos Automplete
     * 
     *
     * @param PqrFormField $PqrFormField
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    private function generateField(PqrFormField $PqrFormField): string
    {
        $name = $PqrFormField->name;
        $required = $PqrFormField->required ? 'required' : '';

        $options = '';
        if ($this->$name) {
            $list = $PqrFormField->getService()->getListDataForAutocomplete(['id' => $this->$name]);
            if ($list) {
                $options .= "<option value='{$list[0]['id']}' selected='selected'>{$list[0]['text']}</option>";
            }
        }

        $code = <<<HTML
    <div class='form-group form-group-default form-group-default-select2 {$required}' id='group_{$name}'>
        <label>{$PqrFormField->label}</label>
        <div class='form-group'>
            <select class='full-width pqrAutocomplete {$required}' name='{$name}' id='{$name}'>
                {$options}
            </select>
        </div>
    </div>
HTML;
        return $code;
    }

    /**
     * Notifica a los funcionarios configurados
     *
     * @return boolean
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    private function sendNotifications(): bool
    {
        $emails = $codes = [];
        $records = $this->PqrForm->PqrNotifications;
        if ($records) {
            foreach ($records as $PqrNotifications) {
                if ($PqrNotifications->email) {
                    $email = $PqrNotifications->Funcionario->email;
                    if (UtilitiesPqr::isEmailValid($email)) {
                        $emails[] = $email;
                    }
                }
                if ($PqrNotifications->notify) {
                    $codes[] = $PqrNotifications->Funcionario->funcionario_codigo;
                }
            }
        }

        if ($codes) {
            $Transfer = new Transfer(
                $this->Documento,
                SessionController::getValue('funcionario_codigo'),
                BuzonSalida::NOMBRE_TRANSFERIDO
            );
            $Transfer->setDestination($codes);
            $Transfer->setDestinationType(Transfer::DESTINATION_TYPE_CODE);
            $Transfer->execute();
        }

        if ($emails) {
            $message = "Cordial Saludo,<br/><br/>Se notifica que se ha generado una solicitud de {$this->PqrForm->label} con radicado {$this->Documento->numero}.<br/><br/>
            El seguimiento lo puede realizar escaneando el código QR o consultando con el número de radicado asignado";

            $SendMailController = new SendMailController(
                "Notificación de {$this->PqrForm->label} # {$this->Documento->numero}",
                $message
            );

            $SendMailController->setDestinations(
                SendMailController::DESTINATION_TYPE_EMAIL,
                $emails
            );

            $send = $SendMailController->send();
            if ($send !== true) {
                $log = [
                    'error' => $send,
                    'message' => "No fue posible notificar a los funcionarios # {$this->Documento->numero}"
                ];
                UtilitiesPqr::notifyAdministrator(
                    "No fue posible notificar a los funcionarios # {$this->Documento->numero}",
                    $log
                );
            }
        }

        return true;
    }

    /**
     * Crea el tercero segun la configuracion del funcionario
     *
     * @return boolean
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    private function createTercero(): bool
    {
        $config = $this->PqrForm->getResponseConfiguration(true);

        if ($config['tercero']) {
            $data = [
                'nombre' => '-',
                'identificacion' => -1,
                'tipo' => Tercero::TIPO_NATURAL,
                'tipo_identificacion' => Tercero::TIPO_IDENTIFICACION_CC,
                'correo' => $this->sys_email
            ];
            foreach ($config['tercero'] as $row) {
                $value = [];
                foreach ($row['value'] as $idPqrFormField) {
                    $name = (new PqrFormField($idPqrFormField))->name;
                    $value[] = trim($this->$name);
                }
                $data[$row['name']] = implode(' ', $value);
            }

            if ($this->sys_anonimo) {
                $data['identificacion'] = -1;
                $data['nombre'] = 'Anónimo';
            }

            $Tercero = Tercero::findByAttributes([
                'identificacion' => $data['identificacion'],
                'estado' => 1
            ]);

            $Tercero ??= new Tercero();
            $TerceroService = new TerceroService($Tercero);
            if(!$TerceroService->save($data)){
                return false;
            }
            $this->sys_tercero = $TerceroService->getModel()->getPK();
            $this->update();
        }

        return true;
    }

    /**
     * Retorna la URL de QR
     *
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function getUrlQR(): string
    {
        $params = [
            'id' => $this->getPK(),
            'documentId' => $this->Documento->getPK()
        ];
        $data = CryptController::encrypt(json_encode($params));

        return sprintf(
            "%s/ws/%s/infoQR.html?data=%s",
            PROTOCOLO_CONEXION . DOMINIO,
            $this->getFormat()->nombre,
            $data
        );
    }

    /**
     * Obtiene el historial de cambios de la PQR
     *
     * @param string $order
     * @return PqrHistory[]
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function getHistory(string $order = 'id desc'): array
    {
        return PqrHistory::findAllByAttributes([
            'idft' => $this->getPK()
        ], [], $order);
    }

    /**
     * Cambia el estado de la PQR
     *
     * @param string $newStatus
     * @param string $observations
     * @return boolean
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function changeStatus(string $newStatus, string $observations = ''): bool
    {
        $actualStatus = $this->sys_estado;

        if ($actualStatus != $newStatus) {
            $this->sys_estado = $newStatus;
            if ($newStatus == FtPqr::ESTADO_TERMINADO) {
                $this->sys_fecha_terminado = date('Y-m-d H:i:s');
            } else {
                $this->sys_fecha_terminado = NULL;
            }
            $this->update(true);

            $history = [
                'fecha' => date('Y-m-d H:i:s'),
                'idft' => $this->getPK(),
                'fk_funcionario' => $this->Funcionario->getPK(),
                'tipo' => PqrHistory::TIPO_CAMBIO_ESTADO,
                'idfk' => 0,
                'descripcion' => "Se actualiza el estado de la solicitud de {$actualStatus} a {$newStatus}. {$observations}"
            ];
            if (!PqrHistory::newRecord($history)) {
                throw new \Exception("No fue posible guardar el historial del cambio", 200);
            }
        }
        return true;
    }
}
