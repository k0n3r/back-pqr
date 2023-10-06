<?php

namespace App\Bundles\pqr\Services;

use App\Bundles\pqr\Services\models\PqrBackup;
use App\Bundles\pqr\Services\models\PqrForm;
use App\Bundles\pqr\Services\models\PqrFormField;
use App\Bundles\pqr\Services\models\PqrNotyMessage;
use App\Bundles\pqr\Services\models\PqrResponseTime;
use App\services\correo\EmailSaia;
use App\services\correo\SendEmailSaia;
use App\services\exception\SaiaException;
use App\services\models\ModelService\ModelService;
use DateInterval;
use DateTime;
use Saia\controllers\anexos\FileJson;
use Saia\controllers\CryptController;
use Saia\controllers\DistributionService;
use Saia\controllers\documento\Transfer;
use Saia\controllers\functions\CoreFunctions;
use Saia\controllers\generator\component\Distribution;
use Saia\controllers\TerceroService;
use Saia\models\documento\Documento;
use Saia\models\formatos\Formato;
use Saia\controllers\DateController;
use Saia\controllers\documento\SaveFt;
use App\Bundles\pqr\formatos\pqr\FtPqr;
use App\Bundles\pqr\helpers\UtilitiesPqr;
use App\Bundles\pqr\Services\models\PqrHistory;
use App\Bundles\pqr\formatos\pqr_respuesta\FtPqrRespuesta;
use Saia\models\tarea\Tarea;
use Saia\models\Tercero;

class FtPqrService extends ModelService
{
    private PqrService $PqrService;

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
     * @return Documento
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2021-07-26
     */
    public function getDocument(): Documento
    {
        return $this->getModel()->getDocument();
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

        $PqrBackup = PqrBackup::findByAttributes([
            'fk_documento' => $this->getModel()->documento_iddocumento,
            'fk_pqr'       => $this->getModel()->getPK(),
        ]);

        $PqrBackupService = $PqrBackup ? $PqrBackup->getService() : (new PqrBackup)->getService();
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
     * Retonar la fecha de vencimiento basado en la fecha de aprobacion
     * y el tipo
     *
     * @return string|DateTime
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function getDateForType(bool $instance = false, ?int $days = null)
    {
        $Created = DateController::getDateTimeFromDataBase($this->getDocument()->fecha);
        $DateTime = (DateController::addBusinessDays(
            $Created,
            is_null($days) ? $this->getDays() : $days
        ));
        $DateTime->setTime(
            $Created->format('H'),
            $Created->format('i'),
            $Created->format('s')
        );

        return $instance ? $DateTime : $DateTime->format('Y-m-d H:i:s');
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

        $history = [
            'tipo'        => PqrHistory::TIPO_ERROR_DIAS_VENCIMIENTO,
            'descripcion' => "No se configuro dias de vencimiento para las opciones seleccionadas por el cliente"
        ];

        $this->saveHistory($history);

        return 1;

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
            'asunto'            => $this->getModel()->getDefaultSubjectForPqrRespuesta()
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
     * Obtiene la fecha de expiracion/vencimiento
     *
     * @return DateTime|string
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
     * Obtiene los datos de finalizacion de timeline
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
            'date'           => $this->getExpirationDate()->format(DateController::PUBLIC_DATE_FORMAT),
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

        $Documento = $this->getDocument();
        $files[] = new FileJson($Documento->getPdfJson());
        $records = $Documento->getService()->getAllFilesAnexos(true);
        foreach ($records as $Anexos) {
            $files[] = new FileJson($Anexos->ruta);
        }

        $EmailSaia = (new EmailSaia())
            ->subject($subject)
            ->htmlWithTemplate($message)
            ->to($this->getModel()->sys_email)
            ->addAttachments($files)
            ->saveShipmentTraceability($Documento->getPK());

        (new SendEmailSaia($EmailSaia))->send();

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
        $this->sendNotificationToInternalDestination();

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

        $Documento = $this->getModel()->getDocument();
        if ($codes) {
            $Transfer = $this->getModel()->getTransferInstance();
            $Transfer->setDestination($codes);
            $Transfer->setDestinationType(Transfer::DESTINATION_TYPE_CODE);
            $Transfer->execute();
        }

        if ($emails) {
            $message = "Cordial Saludo,<br/><br/>Se notifica que se ha generado una solicitud de {$this->getPqrForm()->label} con radicado $Documento->numero.<br/><br/>
            El seguimiento lo puede realizar escaneando el código QR o consultando con el número de radicado asignado";

            $files[] = new FileJson($Documento->getPdfJson());

            $EmailSaia = (new EmailSaia())
                ->subject("Notificación de {$this->getPqrForm()->label} # $Documento->numero")
                ->htmlWithTemplate($message)
                ->to(...$emails)
                ->addAttachments($files)
                ->saveShipmentTraceability($Documento->getPK());

            (new SendEmailSaia($EmailSaia))->send();

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

        if (!$config['tercero']) {
            $this->getErrorManager()->setMessage("Contacte al administrador!, Se debe definir la configuración de la respuesta");
            return false;
        }

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

        return $this->getModel()->save() > 0;
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

        if (!$data['type']) {
            $this->getErrorManager()->setMessage("Error faltan parametros");
            return false;
        }

        if ($this->getPqrService()->subTypeExist() && !$data['subtype']) {
            $this->getErrorManager()->setMessage("Error faltan parametros");
            return false;
        }
        $refreshDescription = false;
        $newAttributes = [];
        $textField = [];
        if ($data['type'] != $this->getModel()->sys_tipo) {
            $oldType = $this->getModel()->getFieldValue(PqrFormField::FIELD_NAME_SYS_TIPO);
            $newAttributes['sys_tipo'] = $data['type'];
            $textField[] = "tipo de '$oldType' a '{newType}'";
            $refreshDescription = true;
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

        $textExpirationDate = $this->getTextForUpdateExpirationDate($data['expirationDate']);
        if ($textExpirationDate) {
            $newAttributes['sys_fecha_vencimiento'] = $data['expirationDate'];
            $textField[] = $textExpirationDate;

            $newAttributes['sys_oportuno'] = $this->getRespuestaOportuna($data['expirationDate']);
            $this->getDocument()->fecha_limite = $data['expirationDate'];
            $this->getDocument()->save();
        }

        if ($data['sys_frecuencia']) {
            $textFrecuencia = $this->getTextForUpdateEstadoFreImpSev('sys_frecuencia', $data['sys_frecuencia']);
            if ($textFrecuencia) {
                $newAttributes['sys_frecuencia'] = $data['sys_frecuencia'];
                $textField[] = "Frecuencia $textFrecuencia";
            }
        }

        if ($data['sys_impacto']) {
            $textImpacto = $this->getTextForUpdateEstadoFreImpSev('sys_impacto', $data['sys_impacto']);
            if ($textImpacto) {
                $newAttributes['sys_impacto'] = $data['sys_impacto'];
                $textField[] = "Impacto $textImpacto";
            }
        }

        if ($data['sys_severidad']) {
            $textSeveridad = $this->getTextForUpdateEstadoFreImpSev('sys_severidad', $data['sys_severidad']);
            if ($textSeveridad) {
                $newAttributes['sys_severidad'] = $data['sys_severidad'];
                $textField[] = "Severidad $textSeveridad";
            }
        }

        if (!$newAttributes) {
            return true;
        }

        $SaveFt = new SaveFt($this->getDocument());
        $SaveFt->edit($newAttributes);
        $this->Model = $this->getDocument()->getFt();

        if($refreshDescription){
            $this->getDocument()->refreshDescription();
        }


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
            'tipo'        => PqrHistory::TIPO_CAMBIO_ESTADO,
            'descripcion' => $text
        ];
        return $this->saveHistory($history);
    }

    public function updateSysOportuno(): bool
    {
        $oldOportuno = $this->getModel()->sys_oportuno;
        $newOportuno = $this->getRespuestaOportuna();

        if ($newOportuno == $oldOportuno) {
            return true;
        }

        if (!$this->save(['sys_oportuno' => $newOportuno])) {
            return false;
        }

        $history = [
            'tipo'        => PqrHistory::TIPO_CAMBIO_ESTADO,
            'descripcion' => "Se actualiza la oportunidad en la respuesta de : $oldOportuno a $newOportuno"
        ];

        return $this->saveHistory($history);
    }

    /**
     * Obtiene Instancias de las respuestas a la PQR
     *
     * @return FtPqrRespuesta[]
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function getPqrAnswers(): array
    {
        $data = [];
        foreach ($this->getModel()->getPqrRespuestas() as $FtPqrRespuesta) {
            $Documento = $FtPqrRespuesta->getDocument();
            if (!$Documento->isActive() && !$Documento->isAvailable()) {
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

        $expirationDate = $this->getExpirationDate();
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

        $date = $this->getExpirationDate()->format(DateController::PUBLIC_DATE_FORMAT);

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

        $expirationDate = $this->getExpirationDate();
        $expirationDate->setTime(0, 0);

        $diff = $now->diff($expirationDate);

        $dias = 0;
        if ($diff->invert) {
            $dias = "<span class='badge badge-danger'>$diff->days</span>";
        }

        return $dias;
    }

    /**
     * Muestra los dias transcurridos desde la radicacion hasta la fecha terminada/actual
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

        return DateController::diasHabilesEntreFechas($DateTime, $now);

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
            "%sws/%s/infoQR.html?data=%s",
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
                $this->getModel()->sys_oportuno = $this->getRespuestaOportuna();
            } else {
                $this->getModel()->sys_fecha_terminado = null;
                $this->getModel()->sys_oportuno = $this->getRespuestaOportuna();
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
    private function getTextForUpdateEstadoFreImpSev(string $fieldName, $value): ?string
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
    private function getTextForUpdateExpirationDate(string $expirationDate): ?string
    {
        $expiration = $this->getExpirationDate();
        if ($expirationDate == $expiration->format('Y-m-d')) {
            return null;
        }

        $oldDate = $expiration->format(DateController::PUBLIC_DATE_FORMAT);

        $newDate = DateController::convertDate(
            $expirationDate,
            DateController::PUBLIC_DATE_FORMAT,
            'Y-m-d'
        );

        return "fecha de vencimiento de $oldDate a $newDate";
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
        $DateTimeForType = $this->getDateForType(true);

        $oldDate = $this->getModel()->sys_fecha_vencimiento ?
            $this->getExpirationDate()->format('Y-m-d')
            : null;

        if ($oldDate != $DateTimeForType->format('Y-m-d')) {

            $this->getModel()->sys_fecha_vencimiento = $DateTimeForType->format('Y-m-d H:i:s');
            $this->getModel()->save();

            $this->getDocument()->fecha_limite = $DateTimeForType->format('Y-m-d H:i:s');
            $this->getDocument()->save();

            $history = [
                'fecha'          => date('Y-m-d H:i:s'),
                'idft'           => $this->getModel()->getPK(),
                'fk_funcionario' => $this->getFuncionario()->getPK(),
                'tipo'           => PqrHistory::TIPO_CAMBIO_VENCIMIENTO,
                'idfk'           => 0,
                'descripcion'    => "Se actualiza la fecha de vencimiento a " .
                    $DateTimeForType->format(DateController::PUBLIC_DATE_FORMAT)
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
     * Registra la distribucion
     *
     * @return boolean
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function saveDistribution(): bool
    {
        if ($this->getDocument()->fromWebservice()) {
            return true;
        }

        $option = (int)$this->getModel()->getKeyField(Distribution::SELECT_MENSAJERIA);

        switch ($option) {
            case FtPqrRespuesta::DISTRIBUCION_RECOGIDA_ENTREGA:
//                $recogida = DistributionService::ESTADO_RECOGIDA;
//                $estado = DistributionService::DISTRIBUCION_POR_RECEPCIONAR;
//                break;

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
        $fieldName = Distribution::DESTINO_INTERNO;

        $DistributionService->start(
            $this->getModel()->sys_tercero,
            DistributionService::TIPO_EXTERNO,
            $this->getModel()->$fieldName,
            DistributionService::TIPO_INTERNO,
            $estado,
            $recogida
        );

        return true;
    }

    private function sendNotificationToInternalDestination(): void
    {
        $FuncionarioDesInt = $this->getModel()->getFuncionarioDestinoInterno();
        if (!$FuncionarioDesInt) {
            return;
        }

        $TareaService = (new Tarea())->getService();
        if (!$TareaService->createOrUpdate($this->getTaskDefaultData())) {
            throw new SaiaException($TareaService->getErrorManager()->getMessage());
        }
    }

    /**
     * Obtiene los datos por defecto para generar la Tarea
     *
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2023-01-23
     */
    protected function getTaskDefaultData(): array
    {
        $FuncionarioDesInt = $this->getModel()->getFuncionarioDestinoInterno();

        $DateTime = $this->getDateForType(true);

        return [
            'tarea'         => 0,
            'nombre'        => 'Resolver PQR # ' . $this->getDocument()->numero,
            'managers'      => [
                [
                    'id'       => $FuncionarioDesInt->getPK(),
                    'external' => 0
                ]
            ],
            'notification'  => 1,// Notificar por Email
            'fecha_inicial' => $this->getTaskDefaultStartDate($DateTime),
            'fecha_final'   => $this->getTaskDefaultEndDate($DateTime),
            'descripcion'   => '',
            'relacion'      => Tarea::RELACION_DOCUMENTO,
            'relacion_id'   => $this->getDocument()->getPK()
        ];
    }

    /**
     * @param DateTime $DateTime
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2023-05-15
     */
    protected function getTaskDefaultStartDate(DateTime $DateTime): string
    {
        return $DateTime->format('Y-m-d H:i:s');
    }

    /**
     * @param DateTime $DateTime
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2023-05-15
     */
    protected function getTaskDefaultEndDate(DateTime $DateTime): string
    {
        $DateTime->add(new DateInterval('PT30M'));

        return $DateTime->format('Y-m-d H:i:s');
    }

    /**
     * @param string|null $date
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2023-06-23
     */
    private function getRespuestaOportuna(?string $date = null): string
    {
        $fExpiration = $date ?? $this->getExpirationDate()->format('Y-m-d');

        $isFinish = $this->getModel()->sys_estado == FtPqr::ESTADO_TERMINADO;

        $fTerminado = new DateTime();
        if ($isFinish) {
            if ($fTerminado->format('Y-m-d') <= $fExpiration) {
                return FtPqr::OPORTUNO_CERRADAS_A_TERMINO;
            }
            return FtPqr::OPORTUNO_CERRADAS_FUERA_DE_TERMINO;
        }

        if ($fTerminado->format('Y-m-d') <= $fExpiration) {
            return FtPqr::OPORTUNO_PENDIENTES_SIN_VENCER;
        }
        return FtPqr::OPORTUNO_VENCIDAS_SIN_CERRAR;
    }

    /**
     * Guarda rastro del cambio en el historial
     *
     * @param array $data
     * @return bool
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2023-06-26
     */
    private function saveHistory(array $data): bool
    {
        $history = array_merge([
            'fecha'          => date('Y-m-d H:i:s'),
            'idft'           => $this->getModel()->getPK(),
            'fk_funcionario' => $this->getFuncionario()->getPK(),
            'idfk'           => 0,
        ], $data);

        $PqrHistoryService = (new PqrHistory())->getService();
        if (!$PqrHistoryService->save($history)) {
            $this->getErrorManager()->setMessage($PqrHistoryService->getErrorManager()->getMessage());
            return false;
        }

        return true;
    }

}
