<?php

namespace App\Bundles\pqr\Services;

use App\Bundles\pqr\Services\models\PqrBackup;
use App\Bundles\pqr\Services\models\PqrForm;
use App\Bundles\pqr\Services\models\PqrFormField;
use App\Bundles\pqr\Services\models\PqrNotyMessage;
use App\Bundles\pqr\Services\models\PqrResponseTime;
use App\services\models\ModelService\ModelService;
use DateTime;
use Saia\controllers\anexos\FileJson;
use Saia\controllers\CryptController;
use Saia\controllers\documento\Transfer;
use Saia\controllers\functions\CoreFunctions;
use Saia\controllers\SendMailController;
use Saia\controllers\TerceroService;
use Saia\models\BuzonSalida;
use Saia\models\documento\Documento;
use Saia\models\formatos\Formato;
use Saia\controllers\DateController;
use Saia\controllers\documento\SaveFt;
use App\Bundles\pqr\formatos\pqr\FtPqr;
use Saia\controllers\SessionController;
use App\Bundles\pqr\helpers\UtilitiesPqr;
use App\Bundles\pqr\Services\models\PqrHistory;
use App\Bundles\pqr\formatos\pqr_respuesta\FtPqrRespuesta;
use Saia\models\Tercero;

class FtPqrService extends ModelService
{
    private PqrService $PqrService;
    private ?Documento $Documento = null;

    const FUNCTION_ADMIN_PQR = 'Administrador PQRS';
    const FUNCTION_ADMIN_DEP_PQR = 'Administrador Dependencia PQRS';

    public function __construct(FtPqr $Ft)
    {
        parent::__construct($Ft);
        $this->PqrService = new PqrService();
    }

    /**
     * Obtiene la instancia de FtPqr actualizada
     *
     * @return FtPqr
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function getModel(): FtPqr
    {
        return $this->Model;
    }

    /**
     * Obtiene el documento del modelo
     *
     * @param bool $force
     * @return Documento
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2021-07-26
     */
    public function getDocument(bool $force = false): Documento
    {
        if (!$this->Documento || $force) {
            $this->Documento = $this->getModel()->getDocument();
        }

        return $this->Documento;
    }

    /**
     * @return PqrService
     * @author Andres Agudelo <andres.agudelo@cerok.com> @date 2021-02-23
     */
    public function getPqrService(): PqrService
    {
        return $this->PqrService;
    }

    /**
     * @return PqrForm
     * @author Andres Agudelo <andres.agudelo@cerok.com> @date 2021-02-23
     */
    public function getPqrForm(): PqrForm
    {
        return $this->getPqrService()->getPqrForm();
    }

    /**
     * Valida si el campo sys_email es valido
     *
     * @return boolean
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function validSysEmail(): bool
    {
        if ($this->getModel()->sys_email) {
            if (!CoreFunctions::isEmailValid($this->getModel()->sys_email)) {
                $this->getErrorManager()->setMessage("Esta dirección de correo ({$this->getModel()->sys_email}) no es válida.");
                return false;
            }
        }
        return true;
    }

    /**
     * Genera el backup del formulario
     *
     * @return boolean
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function createBackup(): bool
    {
        $data = [
            'fk_documento' => $this->getModel()->documento_iddocumento,
            'fk_pqr'       => $this->getModel()->getPK(),
            'data_json'    => json_encode($this->getDataRow())
        ];
        $PqrBackupService = (new PqrBackup)->getService();
        if (!$PqrBackupService->save($data)) {
            $this->getErrorManager()->setMessage("No fue posible registrar el backup");
            return false;
        }

        return true;
    }

    /**
     * Obtiene las valores del modelo para guardarlos en el backup
     *
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    private function getDataRow(): array
    {
        $data = [];
        if ($this->getPqrForm()->show_anonymous) {
            $data = [
                'REGISTRADO COMO ANÓNIMO' => $this->getModel()->sys_anonimo ? 'SI' : 'NO'
            ];
        }

        $Fields = $this->getPqrForm()->getPqrFormFields();
        foreach ($Fields as $PqrFormField) {
            if ($PqrFormField->active) {
                if ($value = $this->getValue($PqrFormField)) {
                    $key = $this->getKey($PqrFormField->label);
                    if (array_key_exists($key, $data)) {
                        $value[$key . "__" . uniqid()] = $value[$key];
                        unset($value[$key]);
                    }
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
     * @date   2020
     */
    private function getValue(PqrFormField $PqrFormField): ?array
    {
        $PqrHtmlField = $PqrFormField->getPqrHtmlField();
        $fieldName = $PqrFormField->name;
        $label = $this->getKey($PqrFormField->label);
        $data = [];

        switch ($PqrHtmlField->type_saia) {
            case 'Hidden':
            case 'Attached':
                break;

            case 'Radio':
            case 'Checkbox':
            case 'Select':
            case 'Date':
                $data[$label] = $this->getModel()->getFieldValue($fieldName);
                break;
            case 'AutocompleteD';
            case 'AutocompleteM';
                $value = null;
                if ($this->getModel()->$fieldName) {
                    $value = $PqrFormField->getService()->getListDataForAutocomplete(['id' => $this->getModel()->$fieldName]);
                }
                $data[$label] = $value ? $value[0]['text'] : '';
                break;
            default:
                $data[$label] = $this->getModel()->$fieldName;
                break;
        }

        return $data;
    }

    /**
     * Obtiene el Key de las registros a guardar
     *
     * @param string $label
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2021-10-04
     */
    private function getKey(string $label): string
    {
        return strtoupper($label);
    }

    /**
     * Actualiza la fecha de vencimiento
     *
     * @return boolean
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function updateFechaVencimiento(): bool
    {
        $FtPqr = $this->getModel();
        $Documento = $this->getDocument();

        $fecha = $this->getDateForType();

        $oldDate = $FtPqr->sys_fecha_vencimiento ?
            DateController::getDateTimeFromDataBase($FtPqr->sys_fecha_vencimiento)->format('Y-m-d H:i:s')
            : null;

        $FtPqr->sys_fecha_vencimiento = $fecha;
        $FtPqr->save();

        $Documento->fecha_limite = $fecha;
        $Documento->save();

        if ($oldDate != $FtPqr->sys_fecha_vencimiento) {
            $history = [
                'fecha'          => date('Y-m-d H:i:s'),
                'idft'           => $this->getModel()->getPK(),
                'fk_funcionario' => $this->getFuncionario()->getPK(),
                'tipo'           => PqrHistory::TIPO_CAMBIO_VENCIMIENTO,
                'idfk'           => 0,
                'descripcion'    => "Se actualiza la fecha de vencimiento a " .
                    DateController::convertDate(
                        $this->getModel()->sys_fecha_vencimiento,
                        DateController::PUBLIC_DATE_FORMAT
                    )
            ];

            $PqrHistoryService = (new PqrHistory)->getService();
            if (!$PqrHistoryService->save($history)) {
                $this->getErrorManager()->setMessage($PqrHistoryService->getErrorManager()->getMessage());
                return false;
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
     * @date   2020
     */
    public function getDateForType(): string
    {
        return (DateController::addBusinessDays(
            DateController::getDateTimeFromDataBase($this->getDocument()->fecha),
            $this->getDays()
        ))->format('Y-m-d H:i:s');
    }

    /**
     * Obtiene los dias configurados como respuesta a la solicitud
     *
     * @return int
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2021-06-06
     */
    private function getDays(): int
    {
        if ($PqrResponseTime = PqrResponseTime::findByAttributes([
            'fk_campo_opciones' => $this->getIdFromResponseTimes(),
            'fk_sys_tipo'       => $this->getModel()->sys_tipo
        ])) {
            return $PqrResponseTime->number_days ?: 1;
        }

        $this->registerErrorResponseTime();

        return 1;

    }

    private function registerErrorResponseTime(): void
    {
        $history = [
            'fecha'          => date('Y-m-d H:i:s'),
            'idft'           => $this->getModel()->getPK(),
            'fk_funcionario' => $this->getFuncionario()->getPK(),
            'tipo'           => PqrHistory::TIPO_ERROR_DIAS_VENCIMIENTO,
            'idfk'           => 0,
            'descripcion'    => "No se configuro dias de vencimiento para los opciones seleccionadas por el cliente"
        ];

        $PqrHistoryService = (new PqrHistory)->getService();
        $PqrHistoryService->save($history);
    }

    /**
     * Obtiene el id del campo seleccionado como
     * tiempo de respuesta
     *
     * @return int
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2021-06-06
     */
    private function getIdFromResponseTimes(): int
    {
        $CamposFormato = $this->getPqrForm()->getCampoFormatoForFieldTime();
        if ($CamposFormato->getPK() == PqrFormField::getSysTipoField()->fk_campos_formato) {
            return -1;
        }
        $nameField = $CamposFormato->nombre;
        return (int)$this->getModel()->$nameField;

    }


    /**
     * Obtiene los campos a cargar en el adicionar
     * de la respuesta
     *
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function getDataToLoadResponse(): array
    {

        if ($Tercero = $this->getModel()->getTercero()) {
            $destino = [
                'id'   => $Tercero->getPK(),
                'text' => "$Tercero->identificacion - $Tercero->nombre"
            ];
        }

        $Formato = Formato::findByAttributes([
            'nombre' => 'pqr_respuesta'
        ]);

        if ($records = $Formato->getField('tipo_distribucion')->getCampoOpciones()) {
            foreach ($records as $CampoOpciones) {
                if ($CampoOpciones->llave == FtPqrRespuesta::DISTRIBUCION_ENVIAR_EMAIL) {
                    $tipoDistribucion = $CampoOpciones->getPK();
                    break;
                }
            }
        }

        if ($records = $Formato->getField('despedida')->getCampoOpciones()) {
            foreach ($records as $CampoOpciones) {
                if ($CampoOpciones->llave == FtPqrRespuesta::ATENTAMENTE_DESPEDIDA) {
                    $despedida = $CampoOpciones->getPK();
                    break;
                }
            }
        }

        return [
            'iddocPqr'          => $this->getDocument()->getPK(),
            'destino'           => $destino ?? 0,
            'tipo_distribucion' => $tipoDistribucion ?? 0,
            'despedida'         => $despedida ?? 0,
            'asunto'            => "Respondiendo a la {$this->getModel()->getFormat()->etiqueta} No {$this->getDocument()->numero}"
        ];
    }

    /**
     * Termina una PQR
     *
     * @param string $observaciones
     * @return boolean
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function finish(string $observaciones = ''): bool
    {
        return $this->changeStatus(
            FtPqr::ESTADO_TERMINADO,
            $observaciones
        );
    }

    /**
     * Obtiene los registros del historial
     *
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2021
     */
    public function getRecordsHistory(): array
    {
        $rows = [];

        foreach ($this->getHistory() as $PqrHistory) {
            $rows[] = array_merge(
                $PqrHistory->getDataAttributes(),
                [
                    'nombre_funcionario' => $PqrHistory->getFuncionario()->getName()
                ]
            );
        }

        return $rows;
    }

    /**
     * Obtiene el email
     *
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function getHistoryForTimeline(): array
    {

        $rows = [];

        $records = $this->getHistory('fecha asc');
        $expirationDate = $this->getExpirationDate();
        $addExpiration = false;

        $rows[] = $this->getInitialRequestData();

        foreach ($records as $PqrHistory) {
            $action = DateController::convertDate($PqrHistory->fecha, 'Y-m-d');
            $actionDate = new DateTime($action);

            if ($actionDate > $expirationDate && !$addExpiration) {
                $rows[] = $this->getDataFinish();
                $addExpiration = true;
            }

            if ($row = $PqrHistory->getService()->getHistoryForTimeline()) {
                $rows[] = $row;
            }
        }

        if (!$addExpiration) {
            $rows[] = $this->getDataFinish();
        }

        return $rows;
    }

    /**
     * Obtiene la fecha de expiracion
     *
     * @return DateTime
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    private function getExpirationDate(): DateTime
    {
        return DateController::getDateTimeFromDataBase($this->getModel()->sys_fecha_vencimiento);

    }

    /**
     * Retonar la informacion inicial de la solicitud para el de timeline
     *
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    private function getInitialRequestData(): array
    {
        return [
            'iconPoint'      => 'fa fa-map-marker',
            'iconPointColor' => 'success',
            'date'           => DateController::convertDate($this->getDocument()->fecha),
            'description'    => "Se registra la solicitud No # {$this->getDocument()->numero}",
            'url'            => UtilitiesPqr::getRoutePdf($this->getDocument())
        ];
    }

    /**
     * Obtiene los datoss de finalizacion de timeline
     *
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    private function getDataFinish(): array
    {
        $type = $this->getModel()->getFieldValue(PqrFormField::FIELD_NAME_SYS_TIPO);

        return [
            'iconPoint'      => 'fa fa-flag-checkered',
            'iconPointColor' => 'success',
            'date'           => DateController::convertDate(
                $this->getModel()->sys_fecha_vencimiento,
                DateController::PUBLIC_DATE_FORMAT
            ),
            'description'    => "Fecha maxima para dar respuesta a la solicitud de tipo $type"
        ];
    }


    /**
     * Notifica al email registrado
     *
     * @return boolean
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function notifyEmail(): bool
    {
        if (!$this->getModel()->sys_email) {
            return true;
        }

        $message = "Cordial Saludo,<br/><br/>Su solicitud ha sido generada con el número de radicado {$this->getDocument()->numero}, adjunto encontrará una copia de la {$this->getPqrForm()->label} diligenciada el día de hoy.<br/><br/>
        El seguimiento lo puede realizar escaneando el código QR o consultando con el número de radicado asignado";
        $subject = "Solicitud de {$this->getPqrForm()->label} # {$this->getDocument()->numero}";

        if ($PqrNotyMessage = PqrNotyMessage::findByAttributes([
            'name' => 'f1_email_solicitante'
        ])) {
            $message = PqrNotyMessageService::resolveVariables($PqrNotyMessage->message_body, $this->getModel());
            $subject = PqrNotyMessageService::resolveVariables($PqrNotyMessage->subject, $this->getModel());
        }

        $SendMailController = new SendMailController(
            $subject,
            $message
        );

        $SendMailController->setDestinations(
            SendMailController::DESTINATION_TYPE_EMAIL,
            [$this->getModel()->sys_email]
        );
        $Documento = $this->getDocument();
        $files[] = new FileJson($Documento->getPdfJson());
        $records = $Documento->getService()->getAllFilesAnexos(true);
        foreach ($records as $Anexos) {
            $files[] = new FileJson($Anexos->ruta);
        }
        $SendMailController->setAttachments($files);
        $SendMailController->saveShipmentTraceability($Documento);

        $send = $SendMailController->send();
        if ($send !== true) {
            $log = [
                'error' => $send
            ];
            UtilitiesPqr::notifyAdministrator(
                "No fue posible notificar la PQR # {$this->getDocument()->numero}",
                $log
            );
        }

        return true;
    }

    /**
     * Html de los campos Automplete
     *
     * @param PqrFormField $PqrFormField
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function generateField(PqrFormField $PqrFormField): string
    {
        $name = $PqrFormField->name;
        $required = $PqrFormField->required ? 'required' : '';

        $options = '';
        if ($this->getModel()->$name) {
            $list = $PqrFormField->getService()->getListDataForAutocomplete(['id' => $this->getModel()->$name]);
            if ($list) {
                $options .= "<option value='{$list[0]['id']}' selected='selected'>{$list[0]['text']}</option>";
            }
        }

        return <<<HTML
    <div class='form-group form-group-default form-group-default-select2 $required' id='group_$name'>
        <label>$PqrFormField->label</label>
        <div class='form-group'>
            <select class='full-width pqrAutocomplete $required' name='$name' id='$name'>
                $options
            </select>
        </div>
    </div>
HTML;
    }

    /**
     * Notifica a los funcionarios configurados
     *
     * @return boolean
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function sendNotifications(): bool
    {
        $emails = $codes = [];
        $records = $this->getPqrForm()->getPqrNotifications();
        if ($records) {
            foreach ($records as $PqrNotification) {
                if ($PqrNotification->email) {
                    $email = $PqrNotification->getFuncionario()->email ?? '';
                    if (CoreFunctions::isEmailValid($email)) {
                        $emails[] = $email;
                    }
                }
                if ($PqrNotification->notify) {
                    $codes[] = $PqrNotification->getFuncionario()->getCode();
                }
            }
        }
        $Documento = $this->getDocument(true);
        if ($codes) {
            $Transfer = new Transfer(
                $Documento,
                SessionController::getValue('funcionario_codigo'),
                BuzonSalida::NOMBRE_TRANSFERIDO
            );
            $Transfer->setDestination($codes);
            $Transfer->setDestinationType(Transfer::DESTINATION_TYPE_CODE);
            $Transfer->execute();
        }

        if ($emails) {
            $message = "Cordial Saludo,<br/><br/>Se notifica que se ha generado una solicitud de {$this->getPqrForm()->label} con radicado $Documento->numero.<br/><br/>
            El seguimiento lo puede realizar escaneando el código QR o consultando con el número de radicado asignado";

            $SendMailController = new SendMailController(
                "Notificación de {$this->getPqrForm()->label} # $Documento->numero",
                $message
            );

            $SendMailController->setDestinations(
                SendMailController::DESTINATION_TYPE_EMAIL,
                $emails
            );

            $files[] = new FileJson($Documento->getPdfJson());
            $SendMailController->setAttachments($files);
            $SendMailController->saveShipmentTraceability($Documento);

            $send = $SendMailController->send();

            if ($send !== true) {
                $log = [
                    'error' => $send
                ];
                UtilitiesPqr::notifyAdministrator(
                    "No fue posible notificar a los funcionarios # $Documento->numero",
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
     * @date   2020
     */
    public function createTercero(): bool
    {
        $config = $this->getPqrForm()->getResponseConfiguration(true);

        if ($config['tercero']) {
            $data = [
                'nombre'              => '-',
                'identificacion'      => Tercero::IDENTIFICACION_INDEFINIDA,
                'tipo'                => Tercero::TIPO_NATURAL,
                'tipo_identificacion' => Tercero::TIPO_IDENTIFICACION_CC,
                'correo'              => $this->getModel()->sys_email
            ];

            foreach ($config['tercero'] as $row) {
                $value = [];
                foreach ($row['value'] as $idPqrFormField) {
                    $PqrFormField = PqrFormField::findByAttributes([
                        'id' => $idPqrFormField
                    ], [
                        'name'
                    ]);

                    if ($PqrFormField) {
                        $name = $PqrFormField->name;
                        $value[] = trim($this->getModel()->$name);
                    }
                }
                $data[$row['name']] = trim(implode(' ', $value));
            }

            if (!$data['identificacion']) {
                $data['identificacion'] = Tercero::IDENTIFICACION_INDEFINIDA;
            }
            if (!$data['nombre']) {
                $data['nombre'] = '-';
            }

            if ($this->getModel()->sys_anonimo && $data['nombre'] == '-') {
                $data['nombre'] = 'Anónimo';
            }

            $Tercero = Tercero::findByAttributes([
                'identificacion' => $data['identificacion'],
                'estado'         => 1
            ]);

            $Tercero ??= new Tercero();
            $TerceroService = new TerceroService($Tercero);
            if (!$TerceroService->save($data)) {
                $this->getErrorManager()->setMessage($TerceroService->getErrorManager()->getMessage());
                return false;
            }
            $this->getModel()->sys_tercero = $TerceroService->getModel()->getPK();
            $this->getModel()->save();
        }

        return true;
    }


    /**
     * Actualiza el tipo de PQR y guarda en el historial
     *
     * @param array $data
     * @return boolean
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function updateType(array $data): bool
    {

        if (
            !$data['type'] ||
            !$data['sys_frecuencia'] ||
            !$data['sys_impacto'] ||
            !$data['sys_severidad']
        ) {
            $this->getErrorManager()->setMessage("Error faltan parametros");
            return false;
        }

        if ($this->getPqrService()->subTypeExist() && !$data['subtype']) {
            $this->getErrorManager()->setMessage("Error faltan parametros");
            return false;
        }

        $newAttributes = [];
        $textField = [];
        if ($data['type'] != $this->getModel()->sys_tipo) {
            $oldType = $this->getModel()->getFieldValue(PqrFormField::FIELD_NAME_SYS_TIPO);
            $newAttributes['sys_tipo'] = $data['type'];
            $textField[] = "tipo de $oldType a {newType}";
        }

        if ($this->getPqrService()->subTypeExist()) {
            if ($data['subtype'] != $this->getModel()->sys_subtipo) {
                $oldSubType = $this->getModel()->getFieldValue('sys_subtipo');
                if (!$oldSubType) {
                    $oldSubType = '-';
                }
                $newAttributes['sys_subtipo'] = $data['subtype'];
                $textField[] = "categoria/subtipo de $oldSubType a {newSubType}";
            }
        }

        if ($this->getPqrService()->dependencyExist()) {
            if ($data['dependency'] != $this->getModel()->sys_dependencia) {
                $oldDependency = $this->getValueForReport('sys_dependencia');
                if (!$oldDependency) {
                    $oldDependency = '-';
                }
                $newAttributes['sys_dependencia'] = $data['dependency'];
                $textField[] = "dependencia de $oldDependency a {newDependency}";
            }
        }


        $textExpirationDate = $this->updateExpirationDate($data['expirationDate']);
        if ($textExpirationDate) {
            $newAttributes['sys_fecha_vencimiento'] = $data['expirationDate'];
            $textField[] = $textExpirationDate;
        }

        $textFrecuencia = $this->updateEstadoFreImpSev('sys_frecuencia', $data['sys_frecuencia']);
        if ($textFrecuencia) {
            $newAttributes['sys_frecuencia'] = $data['sys_frecuencia'];
            $textField[] = "Frecuencia $textFrecuencia";
        }

        $textImpacto = $this->updateEstadoFreImpSev('sys_impacto', $data['sys_impacto']);
        if ($textImpacto) {
            $newAttributes['sys_impacto'] = $data['sys_impacto'];
            $textField[] = "Impacto $textImpacto";
        }

        $textSeveridad = $this->updateEstadoFreImpSev('sys_severidad', $data['sys_severidad']);
        if ($textSeveridad) {
            $newAttributes['sys_severidad'] = $data['sys_severidad'];
            $textField[] = "Severidad $textSeveridad";
        }

        if (!$newAttributes) {
            return true;
        }

        $SaveFt = new SaveFt($this->getDocument());
        $SaveFt->edit($newAttributes);
        $this->Model = $this->getDocument()->getFt();

        $text = "Se actualiza: " . implode(', ', $textField);
        $newType = $this->getModel()->getFieldValue(PqrFormField::FIELD_NAME_SYS_TIPO);
        $newSubType = $this->getPqrService()->subTypeExist() ? $this->getModel()->getFieldValue('sys_subtipo') : '';
        $newDependency = $this->getPqrService()->dependencyExist() ? $this->getValueForReport('sys_dependencia') : '';

        $text = str_replace([
            '{newType}',
            '{newSubType}',
            '{newDependency}'
        ], [
            $newType,
            $newSubType,
            $newDependency
        ], $text);

        $history = [
            'fecha'          => date('Y-m-d H:i:s'),
            'idft'           => $this->getModel()->getPK(),
            'fk_funcionario' => $this->getFuncionario()->getPK(),
            'tipo'           => PqrHistory::TIPO_CAMBIO_ESTADO,
            'idfk'           => 0,
            'descripcion'    => $text
        ];

        $PqrHistoryService = (new PqrHistory)->getService();
        if (!$PqrHistoryService->save($history)) {
            $this->getErrorManager()->setMessage($PqrHistoryService->getErrorManager()->getMessage());
            return false;
        }

        return true;
    }

    /**
     * Obtiene Instancias de las respuestas a la PQR
     *
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function getPqrAnswers(): array
    {
        $data = [];
        foreach ($this->getModel()->getPqrRespuestas() as $FtPqrRespuesta) {
            $Documento = $FtPqrRespuesta->getDocument();
            if (!$Documento->isActive() && !$Documento->isDeleted()) {
                $data[] = $FtPqrRespuesta;
            }
        }
        return $data;
    }

    /**
     * Obtiene la fecha de vencimiento con el color que identifica
     * el tiempo pendiente por responder la PQR
     *
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function getColorExpiration(): string
    {
        if (!$this->getModel()->sys_fecha_vencimiento) {
            return 'Fecha vencimiento no configurada';
        }

        $now = $this->getModel()->sys_fecha_terminado ? DateController::getDateTimeFromDataBase($this->getModel()->sys_fecha_terminado) : new DateTime();
        $now->setTime(0, 0);

        $expirationDate = DateController::getDateTimeFromDataBase($this->getModel()->sys_fecha_vencimiento);
        $now->setTime(0, 0);

        $diff = $now->diff($expirationDate);

        $color = "success";
        if ($diff->invert || $diff->days <= FtPqr::VENCIMIENTO_ROJO) {
            $color = 'danger';
        } else {
            if ($diff->days <= FtPqr::VENCIMIENTO_AMARILLO) {
                $color = 'warning';
            }
        }

        $date = DateController::convertDate(
            $this->getModel()->sys_fecha_vencimiento,
            DateController::PUBLIC_DATE_FORMAT
        );

        return "<span class='badge badge-$color'>$date</span>";
    }


    /**
     * Muestra la fecha finalizacion
     *
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function getEndDate(): string
    {
        if (!$this->getModel()->sys_fecha_terminado) {
            return 'Fecha fin no configurada';
        }

        return DateController::convertDate(
            $this->getModel()->sys_fecha_terminado,
            DateController::PUBLIC_DATE_FORMAT
        );
    }

    /**
     * Muestra los dias de retraso al solucionar la pqr
     *
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function getDaysLate(): string
    {
        if (!$this->getModel()->sys_fecha_vencimiento) {
            return 'Fecha vencimiento no configurada';
        }

        if (!$this->getModel()->sys_fecha_terminado) {
            return 'Fecha fin no configurada';
        }

        $now = new DateTime($this->getModel()->sys_fecha_terminado);
        $now->setTime(0, 0);

        $expirationDate = new DateTime($this->getModel()->sys_fecha_vencimiento);
        $expirationDate->setTime(0, 0);

        $diff = $now->diff($expirationDate);

        $dias = 0;
        if ($diff->invert) {
            $dias = "<span class='badge badge-danger'>$diff->days</span>";
        }

        return $dias;
    }

    /**
     * Muestra los dias transcurridos desde la radicacion hasta la fecha actual
     *
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function getDaysWait(): string
    {
        $now = !$this->getModel()->sys_fecha_terminado ? new DateTime()
            : DateController::getDateTimeFromDataBase($this->getModel()->sys_fecha_terminado);
        $now->setTime(0, 0);

        $DateTime = DateController::getDateTimeFromDataBase($this->getDocument()->fecha);
        $DateTime->setTime(0, 0);

        $diff = $now->diff($DateTime);

        return $diff->days;
    }

    /**
     * Obtiene el valor del campo que mostrara en el reporte
     *
     * @param string $name
     * @return string|null
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function getValueForReport(string $name): ?string
    {
        $value = $this->getValue($this->getPqrForm()->getRow($name));

        return $value ? implode(',', $value) : null;
    }

    /**
     * Obtiene el historial de cambios de la PQR
     *
     * @param string $order
     * @return PqrHistory[]
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function getHistory(string $order = 'id desc'): array
    {
        return PqrHistory::findAllByAttributes([
            'idft' => $this->getModel()->getPK()
        ], [], $order);
    }


    /**
     * Retorna la URL de QR
     *
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function getUrlQR(): string
    {
        $params = [
            'id'         => $this->getModel()->getPK(),
            'documentId' => $this->getDocument()->getPK()
        ];
        $data = CryptController::encrypt(json_encode($params));

        return sprintf(
            "%s/ws/%s/infoQR.html?data=%s",
            $_SERVER['APP_DOMAIN'],
            $this->getModel()->getFormat()->nombre,
            $data
        );
    }


    /**
     * Cambia el estado de la PQR
     *
     * @param string $newStatus
     * @param string $observations
     * @return boolean
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function changeStatus(string $newStatus, string $observations = ''): bool
    {
        $actualStatus = $this->getModel()->sys_estado;

        if ($actualStatus != $newStatus) {
            $this->getModel()->sys_estado = $newStatus;
            if ($newStatus == FtPqr::ESTADO_TERMINADO) {
                $this->getModel()->sys_fecha_terminado = date('Y-m-d H:i:s');
            } else {
                $this->getModel()->sys_fecha_terminado = null;
                $this->getModel()->setSaveNullAttributes(true);
            }
            $this->getModel()->save();

            $history = [
                'fecha'          => date('Y-m-d H:i:s'),
                'idft'           => $this->getModel()->getPK(),
                'fk_funcionario' => $this->getFuncionario()->getPK(),
                'tipo'           => PqrHistory::TIPO_CAMBIO_ESTADO,
                'idfk'           => 0,
                'descripcion'    => "Se actualiza el estado de la solicitud de $actualStatus a $newStatus. $observations"
            ];

            $PqrHistoryService = (new PqrHistory)->getService();
            if (!$PqrHistoryService->save($history)) {
                $this->getErrorManager()->setMessage($PqrHistoryService->getErrorManager()->getMessage());
                return false;
            }
        }
        return true;
    }

    /**
     * @param string $fieldName
     * @param        $value
     * @return string|null
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2021-10-05
     */
    private function updateEstadoFreImpSev(string $fieldName, $value): ?string
    {
        if ($value == $this->getModel()->$fieldName) {
            return null;
        }

        $newValue = $this->getModel()->getValueLabel($fieldName, $value);
        if (!$this->getModel()->$fieldName) {
            $text = "a $newValue";
        } else {
            $oldType = $this->getModel()->getValueLabel($fieldName);
            $text = "de $oldType a $newValue";
        }

        return $text;
    }

    /**
     * @param string $expirationDate
     * @return string|null
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2021-10-05
     */
    private function updateExpirationDate(string $expirationDate): ?string
    {
        $expiration = DateController::convertDate($this->getModel()->sys_fecha_vencimiento, 'Y-m-d');
        if ($expirationDate == $expiration) {
            return null;
        }

        $this->getDocument()->fecha_limite = $expirationDate;
        $this->getDocument()->save();

        $oldDate = DateController::convertDate(
            $expiration,
            DateController::PUBLIC_DATE_FORMAT,
            'Y-m-d'
        );

        $newDate = DateController::convertDate(
            $expirationDate,
            DateController::PUBLIC_DATE_FORMAT,
            'Y-m-d'
        );

        return "fecha de vencimiento de $oldDate a $newDate";
    }

}
