<?php

namespace App\Bundles\pqr\Services;

use App\Bundles\pqr\formatos\pqr\FtPqr;
use App\Exception\ValidationFailedException;
use App\Bundles\pqr\formatos\pqr_respuesta\FtPqrRespuesta;
use App\Bundles\pqr\Service\PqrService;
use App\Bundles\pqr\Entity\PqrBackup as PqrBackupEntity;
use App\Bundles\pqr\Entity\PqrBalancer as PqrBalancerEntity;
use App\Bundles\pqr\Entity\PqrFormField as PqrFormFieldEntity;
use App\Bundles\pqr\Entity\PqrHistory as PqrHistoryEntity;
use App\Bundles\pqr\Service\PqrHistoryService;
use App\Bundles\pqr\Entity\PqrNotyMessage as PqrNotyMessageEntity;
use App\Bundles\pqr\Service\PqrNotyMessageService;
use App\Bundles\pqr\Entity\PqrResponseTime as PqrResponseTimeEntity;
use App\Bundles\pqr\Repository\PqrBackupRepository;
use App\Bundles\pqr\Repository\PqrBalancerRepository;
use App\Bundles\pqr\Repository\PqrFormFieldRepository;
use App\Bundles\pqr\Repository\PqrHistoryRepository;
use App\Bundles\pqr\Repository\PqrNotyMessageRepository;
use App\Bundles\pqr\Repository\PqrResponseTimeRepository;
use App\Bundles\pqr\Entity\PqrForm as PqrFormEntity;
use App\Bundles\pqr\Entity\PqrNotification as PqrNotificationEntity;
use App\Bundles\pqr\Repository\PqrFormRepository;
use App\Bundles\pqr\Repository\PqrNotificationRepository;
use App\Bundles\pqr\Service\PqrFormFieldServiceFactory;
use Saia\models\formatos\CamposFormato;
use Doctrine\ORM\EntityManagerInterface;
use Saia\models\Funcionario;
use App\EventSubscriber\Mailer\MailSubscriber;
use App\services\models\ModelService\ModelService;
use DateInterval;
use DateTime;
use Doctrine\DBAL\ParameterType;
use InvalidArgumentException;
use Saia\controllers\CryptController;
use Saia\controllers\DateController;
use Saia\controllers\DistributionService;
use Saia\controllers\documento\SaveFt;
use Saia\controllers\documento\Transfer;
use Saia\controllers\functions\CoreFunctions;
use Saia\controllers\generator\component\Distribution;
use Saia\controllers\TerceroService;
use Saia\models\documento\Documento;
use Saia\models\formatos\Formato;
use Saia\models\grupo\Grupo;
use Saia\models\tarea\Tarea;
use Saia\models\Tercero;
use Saia\models\vistas\VfuncionarioDc;
use Symfony\Component\Mime\Email;
use Throwable;

class FtPqrService extends ModelService
{
    public const string FUNCTION_ADMIN_PQR     = 'Administrador PQRS';
    public const string FUNCTION_ADMIN_DEP_PQR = 'Administrador Dependencia PQRS';

    public function getModel(): FtPqr
    {
        return $this->Model;
    }

    public function getDocument(): Documento
    {
        return $this->getModel()->getDocument();
    }

    public function getPqrService(): PqrService
    {
        return $this->serviceLocator->get(PqrService::class);
    }

    /**
     * @return PqrFormEntity
     * @author Andres Agudelo <andres.agudelo@cerok.com> @date 2021-02-23
     */
    public function getPqrForm(): PqrFormEntity
    {
        return $this->getPqrFormRepository()->findActiveOrFail();
    }

    /**
     * Valida si el campo sys_email es valido
     *
     * @return void
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function validSysEmail(): void
    {
        if ($this->getModel()->sys_email) {
            if (!CoreFunctions::isEmailValid($this->getModel()->sys_email)) {
                throw new ValidationFailedException(
                    $this->serviceLocator->getTranslator()->trans(
                        'correo_invalido',
                        ['%email%' => $this->getModel()->sys_email],
                    ),
                );
            }
        }
    }

    /**
     * Genera el backup del formulario
     *
     * @return bool
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function createBackup(): bool
    {
        $backup = $this->getPqrBackupRepository()->findOneBy([
            'fkDocumento' => (int)$this->getModel()->documento_iddocumento,
            'fkPqr'       => $this->getModel()->getPK(),
        ]);

        if (!$backup) {
            $backup = new PqrBackupEntity();
            $backup->setFkDocumento((int)$this->getModel()->documento_iddocumento);
            $backup->setFkPqr($this->getModel()->getPK());
            $this->getEntityManager()->persist($backup);
        }

        $backup->setDataJson($this->getDataRow());
        $this->getEntityManager()->flush();

        return true;
    }

    /**
     * Obtiene las valores del modelo para guardarlos en el backup
     *
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    protected function getDataRow(): array
    {
        $data = [];
        if ($this->getPqrForm()->isShowAnonymous()) {
            $data = [
                'REGISTRADO COMO ANÓNIMO' => $this->getModel()->sys_anonimo ? 'SI' : 'NO',
            ];
        }

        $Fields = $this->getPqrFormFieldRepository()->findByPqrFormOrdered($this->getPqrForm()->getId());
        foreach ($Fields as $PqrFormField) {
            if ($PqrFormField->isActive()) {
                if ($value = $this->getValue($PqrFormField)) {
                    $key = $this->getKey($PqrFormField->getLabel());
                    if (array_key_exists($key, $data)) {
                        $value[$key."__".uniqid()] = $value[$key];
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
     * @param PqrFormFieldEntity $PqrFormField
     *
     * @return array|null
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    protected function getValue(PqrFormFieldEntity $PqrFormField): ?array
    {
        $PqrHtmlField = $PqrFormField->getHtmlField();
        $fieldName    = $PqrFormField->getName();
        $label        = $this->getKey($PqrFormField->getLabel());
        $data         = [];

        switch ($PqrHtmlField->getTypeSaia()) {
            case 'Hidden':
            case 'Attached':
                break;

            case 'Radio':
            case 'Checkbox':
            case 'Select':
            case 'Date':
                $data[$label] = $this->getModel()->getFieldValue($fieldName);
                break;
            case 'AutocompleteD':
            case 'AutocompleteM':
                $value = null;
                if ($this->getModel()->$fieldName) {
                    $value = $this->serviceLocator
                        ->get(PqrFormFieldServiceFactory::class)
                        ->create($PqrFormField->getId())
                        ->getListDataForAutocomplete(['id' => $this->getModel()->$fieldName]);
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
     *
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2021-10-04
     */
    protected function getKey(string $label): string
    {
        return strtoupper($label);
    }

    /**
     * Retonar la fecha de vencimiento basado en la fecha de aprobacion
     * y el tipo
     *
     * @param bool     $instance
     * @param int|null $days
     *
     * @return string|DateTime
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     */
    public function getDateForType(bool $instance = false, ?int $days = null): DateTime|string
    {
        $days    = is_null($days) ? $this->getDays() : $days;
        $Created = DateController::getDateTimeFromDataBase($this->getDocument()->fecha);
        if ($this->isEnabledCalendarDays()) {
            $DateTime = clone $Created;
            $interval = sprintf("P%sD", $days);
            $DateTime->add(new DateInterval($interval));
        } else {
            $DateTime = (DateController::addBusinessDays($Created, $days));
        }

        $DateTime->setTime(
            $Created->format('H'),
            $Created->format('i'),
            $Created->format('s'),
        );

        return $instance ? $DateTime : $DateTime->format('Y-m-d H:i:s');
    }

    /**
     * Valida si los dias se cuentan de corrido o tiene en cuenta los festivos
     *
     * @return bool
     * @author Andres Agudelo <andres.agudelo@saiasoftware.com> 2025-10-28
     */
    protected function isEnabledCalendarDays(): bool
    {
        return $this->getPqrForm()->isEnabledCalendarDays();
    }

    /**
     * Obtiene los dias configurados como respuesta a la solicitud
     *
     * @return int
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2021-06-06
     */
    protected function getDays(): int
    {
        $pqrRt = $this->getPqrResponseTimeRepository()->findOneBy([
            'fkCampoOpciones' => $this->getIdFromResponseTimes(),
            'fkSysTipo'       => $this->getModel()->sys_tipo,
        ]);
        if ($pqrRt) {
            return $pqrRt->getNumberDays() ?: 1;
        }

        $history = [
            'tipo'        => PqrHistoryEntity::TIPO_ERROR_DIAS_VENCIMIENTO,
            'descripcion' => "No se configuro dias de vencimiento para las opciones seleccionadas por el cliente",
        ];

        $this->saveHistory($history);

        return 1;
    }

    public function getFuncionarioFromBalacer(): ?VfuncionarioDc
    {
        $pqrBalancer = $this->getPqrBalancerRepository()->findOneBy([
            'fkCampoOpciones' => $this->getIdFromBalancer(),
            'fkSysTipo'       => $this->getModel()->sys_tipo,
        ]);
        if ($pqrBalancer) {
            $fkGrupo = $pqrBalancer->getFkGrupo();
            if ($fkGrupo > 0) {
                return $this->getFuncionarioFromGroup(new Grupo($fkGrupo));
            }
        }

        return null;
    }

    /**
     * Obtiene el id del campo seleccionado como
     * tiempo de respuesta
     *
     * @return int
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2021-06-06
     */
    protected function getIdFromResponseTimes(): int
    {
        $CamposFormato = new CamposFormato($this->getPqrForm()->getFkFieldTime());
        $sysTipoField  = $this->getPqrFormFieldRepository()->findSysTipo();
        if ($CamposFormato->getPK() == ($sysTipoField?->getFkCamposFormato() ?? 0)) {
            return -1;
        }
        $nameField = $CamposFormato->nombre;

        return (int)$this->getModel()->$nameField;
    }

    protected function getIdFromBalancer(): int
    {
        $CamposFormato = new CamposFormato($this->getPqrForm()->getFkFieldBalancer());
        $sysTipoField  = $this->getPqrFormFieldRepository()->findSysTipo();
        if ($CamposFormato->getPK() == ($sysTipoField?->getFkCamposFormato() ?? 0)) {
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
                'text' => "$Tercero->identificacion - $Tercero->nombre",
            ];
        }

        $Formato = Formato::findByAttributes([
            'nombre' => 'pqr_respuesta',
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
            'asunto'            => $this->getModel()->getDefaultSubjectForPqrRespuesta(),
        ];
    }

    /**
     * Termina una PQR
     *
     * @param string $observaciones
     *
     * @return void
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function finish(string $observaciones = ''): void
    {
        $this->changeStatus(FtPqr::ESTADO_TERMINADO, $observaciones);
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

        foreach ($this->getHistory() as $pqrHistory) {
            $rows[] = [
                'id'                 => $pqrHistory->getId(),
                'fecha'              => $pqrHistory->getFecha()->format('Y-m-d H:i:s'),
                'idft'               => $pqrHistory->getIdft(),
                'fk_funcionario'     => $pqrHistory->getFkFuncionario(),
                'tipo'               => $pqrHistory->getTipo(),
                'idfk'               => $pqrHistory->getIdfk(),
                'descripcion'        => $pqrHistory->getDescripcion(),
                'nombre_funcionario' => (new Funcionario($pqrHistory->getFkFuncionario()))->getName(),
            ];
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

        $records        = $this->getHistory('fecha asc');
        $expirationDate = $this->getExpirationDate();
        $addExpiration  = false;

        $rows[] = $this->getInitialRequestData();

        foreach ($records as $PqrHistory) {
            $action     = $PqrHistory->getFecha()->format('Y-m-d');
            $actionDate = new DateTime($action);

            if ($actionDate > $expirationDate && !$addExpiration) {
                $rows[]        = $this->getDataFinish();
                $addExpiration = true;
            }

            if ($row = $this->getPqrHistoryService()->getHistoryForTimeline($PqrHistory)) {
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
     * @return DateTime
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    protected function getExpirationDate(): DateTime
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
    protected function getInitialRequestData(): array
    {
        return [
            'iconPoint'      => 'fa fa-map-marker',
            'iconPointColor' => 'success',
            'date'           => DateController::convertDate($this->getDocument()->fecha),
            'description'    => "Se registra la solicitud No # {$this->getDocument()->numero}",
            'url'            => $this->buildPdfUrl(),
        ];
    }

    /**
     * Obtiene los datos de finalizacion de timeline
     *
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    protected function getDataFinish(): array
    {
        $type = $this->getModel()->getFieldValue(PqrFormFieldEntity::FIELD_NAME_SYS_TIPO);

        return [
            'iconPoint'      => 'fa fa-flag-checkered',
            'iconPointColor' => 'success',
            'date'           => $this->getExpirationDate()->format(DateController::PUBLIC_DATE_FORMAT),
            'description'    => "Fecha maxima para dar respuesta a la solicitud de tipo $type",
        ];
    }

    /**
     * Notifica al email registrado
     *
     * @return bool
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function notifyEmail(): bool
    {
        if (!$this->getModel()->sys_email) {
            return true;
        }

        $message = "Cordial Saludo,<br/><br/>Su solicitud ha sido generada con el radicado {$this->getDocument()->getService()->getFilingReferenceNumber()}, adjunto encontrará una copia de la {$this->getPqrForm()->getLabel()} diligenciada el día de hoy.<br/><br/>
        El seguimiento lo puede realizar escaneando el código QR o consultando con el número de consecutivo asignado";
        $subject = "Solicitud de {$this->getPqrForm()->getLabel()} # {$this->getDocument()->numero}";

        if ($PqrNotyMessage = $this->getPqrNotyMessageRepository()->findByName('f1_email_solicitante')) {
            $message = PqrNotyMessageService::resolveVariables(
                $PqrNotyMessage->getMessageBody() ?? '',
                $this->getModel(),
            );
            $subject = PqrNotyMessageService::resolveVariables($PqrNotyMessage->getSubject() ?? '', $this->getModel());
        }

        $email = (new Email());

        $Documento = $this->getDocument();
        $file      = $Documento->getPdfFile();
        $email->attach($file->getContent(), $file->getSplFileInfo()->getFilename());

        $records = $Documento->getService()->getAllFilesAnexos(true);
        foreach ($records as $Anexos) {
            $file = $this->serviceLocator->getFileResolver()->fromStoragePath($Anexos->ruta);
            $email->attach($file->getContent(), basename($Anexos->ruta));
        }

        $email
            ->subject($subject)
            ->html($message)
            ->to($this->getModel()->sys_email);

        $params = [
            'documentId' => $Documento->getPK(),
        ];

        $email->getHeaders()->addTextHeader(
            MailSubscriber::HEADER_METADATA,
            json_encode($params),
        );

        $this->serviceLocator->getMailerService()->send($email, 'pqr.radicado.solicitante');

        return true;
    }

    /**
     * Html de los campos Automplete
     *
     * @param PqrFormFieldEntity $PqrFormField $PqrFormField
     *
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function generateField(PqrFormFieldEntity $PqrFormField): string
    {
        $name     = $PqrFormField->getName();
        $required = $PqrFormField->isRequired() ? 'required' : '';

        $options = '';
        if ($this->getModel()->$name) {
            $list = $this->serviceLocator
                ->get(PqrFormFieldServiceFactory::class)
                ->create($PqrFormField->getId())
                ->getListDataForAutocomplete(['id' => $this->getModel()->$name]);
            if ($list) {
                $options .= "<option value='{$list[0]['id']}' selected='selected'>{$list[0]['text']}</option>";
            }
        }

        return <<<HTML
            <div class='form-group form-group-default form-group-default-select2 $required' id='group_$name'>
                <label>{$PqrFormField->getLabel()}</label>
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
     * @return bool
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function sendNotifications(): bool
    {
        $this->sendNotificationToInternalDestination();

        $emails  = $codes = [];
        $records = $this->getPqrNotificationRepository()->findByPqrForm($this->getPqrForm()->getId());
        if ($records) {
            foreach ($records as $PqrNotification) {
                if ($PqrNotification->isEmail()) {
                    $email = (new Funcionario($PqrNotification->getFkFuncionario()))->email ?? '';
                    if (CoreFunctions::isEmailValid($email)) {
                        $emails[] = $email;
                    }
                }
                if ($PqrNotification->isNotify()) {
                    $codes[] = (new Funcionario($PqrNotification->getFkFuncionario()))->getCode();
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
            $message = "Cordial Saludo,<br/><br/>Se notifica que se ha generado una solicitud de {$this->getPqrForm()->getLabel()} con radicado {$Documento->getService()->getFilingReferenceNumber()}.<br/><br/>
            El seguimiento lo puede realizar escaneando el código QR o consultando con el número de consecutivo asignado";
            $email   = (new Email())
                ->subject("Notificación de {$this->getPqrForm()->getLabel()} # $Documento->numero")
                ->html($message)
                ->to(...$emails);

            $file = $Documento->getPdfFile();
            $email->attach($file->getContent(), $file->getSplFileInfo()->getFilename());

            $params = [
                'documentId' => $Documento->getPK(),
            ];

            $email->getHeaders()->addTextHeader(
                MailSubscriber::HEADER_METADATA,
                json_encode($params),
            );

            $this->serviceLocator->getMailerService()->send($email, 'pqr.radicado.configurados');
        }

        return true;
    }

    /**
     * Crea el tercero segun la configuracion del funcionario
     *
     * @return void
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function createTercero(): void
    {
        $config = $this->getPqrForm()->getResponseConfiguration();

        if (!$config['tercero']) {
            throw new ValidationFailedException(
                $this->serviceLocator->getTranslator(
                )->trans('configuracion_respuesta_no_definida'),
            );
        }

        $data = [
            'nombre'              => '-',
            'identificacion'      => Tercero::IDENTIFICACION_INDEFINIDA,
            'tipo'                => Tercero::TIPO_NATURAL,
            'tipo_identificacion' => Tercero::TIPO_IDENTIFICACION_CC,
            'correo'              => $this->getModel()->sys_email,
        ];

        foreach ($config['tercero'] as $row) {
            $value = [];
            foreach ($row['value'] as $idPqrFormField) {
                $pqrFormFieldEntity = $this->getPqrFormFieldRepository()->find($idPqrFormField);

                if ($pqrFormFieldEntity) {
                    $name    = $pqrFormFieldEntity->getName();
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
            'estado'         => 1,
        ]);

        $Tercero        ??= new Tercero();
        $TerceroService = new TerceroService($Tercero);
        if (!$TerceroService->save($data)) {
            throw new ValidationFailedException(
                $this->serviceLocator->getTranslator(
                )->trans('no_fue_posible_guardar_tercero'),
            );
        }
        $this->getModel()->sys_tercero = $TerceroService->getModel()->getPK();

        if (!$this->getModel()->save()) {
            throw new ValidationFailedException(
                $this->serviceLocator->getTranslator(
                )->trans('no_fue_posible_guardar_pqr'),
            );
        }
    }

    /**
     * Actualiza el tipo de PQR y guarda en el historial
     *
     * @param array $data
     *
     * @return void
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function updateType(array $data): void
    {
        if (!$data['type']) {
            throw new ValidationFailedException(
                $this->serviceLocator->getTranslator()->trans(
                    'error_faltan_parametros',
                ),
            );
        }

        if ($this->getPqrService()->subTypeExist() && !$data['subtype']) {
            throw new ValidationFailedException(
                $this->serviceLocator->getTranslator()->trans(
                    'error_faltan_parametros',
                ),
            );
        }
        $refreshDescription = false;
        $newAttributes      = [];
        $textField          = [];
        if ($data['type'] != $this->getModel()->sys_tipo) {
            $oldType                   = $this->getModel()->getFieldValue(PqrFormFieldEntity::FIELD_NAME_SYS_TIPO);
            $newAttributes['sys_tipo'] = $data['type'];
            $textField[]               = "tipo de '$oldType' a '{newType}'";
            $refreshDescription        = true;
        }

        if ($this->getPqrService()->subTypeExist()) {
            if ($data['subtype'] != $this->getModel()->sys_subtipo) {
                $oldSubType = $this->getModel()->getFieldValue('sys_subtipo');
                if (!$oldSubType) {
                    $oldSubType = '-';
                }
                $newAttributes['sys_subtipo'] = $data['subtype'];
                $textField[]                  = "categoria/subtipo de $oldSubType a {newSubType}";
            }
        }

        if ($this->getPqrService()->dependencyExist()) {
            if ($data['dependency'] != $this->getModel()->sys_dependencia) {
                $oldDependency = $this->getValueForReport(PqrFormFieldEntity::FIELD_NAME_SYS_DEPENDENCIA);
                if (!$oldDependency) {
                    $oldDependency = '-';
                }
                $newAttributes[PqrFormFieldEntity::FIELD_NAME_SYS_DEPENDENCIA] = $data['dependency'];
                $textField[]                                                   = "dependencia de $oldDependency a {newDependency}";
            }
        }

        $textExpirationDate = $this->getTextForUpdateExpirationDate($data['expirationDate']);
        if ($textExpirationDate) {
            $newAttributes['sys_fecha_vencimiento'] = $data['expirationDate'];
            $textField[]                            = $textExpirationDate;

            $newAttributes['sys_oportuno']     = $this->getRespuestaOportuna($data['expirationDate']);
            $this->getDocument()->fecha_limite = $data['expirationDate'];
            $this->getDocument()->save();
        }

        if ($data['sys_frecuencia']) {
            $textFrecuencia = $this->getTextForUpdateEstadoFreImpSev('sys_frecuencia', $data['sys_frecuencia']);
            if ($textFrecuencia) {
                $newAttributes['sys_frecuencia'] = $data['sys_frecuencia'];
                $textField[]                     = "Frecuencia $textFrecuencia";
            }
        }

        if ($data['sys_impacto']) {
            $textImpacto = $this->getTextForUpdateEstadoFreImpSev('sys_impacto', $data['sys_impacto']);
            if ($textImpacto) {
                $newAttributes['sys_impacto'] = $data['sys_impacto'];
                $textField[]                  = "Impacto $textImpacto";
            }
        }

        if ($data['sys_severidad']) {
            $textSeveridad = $this->getTextForUpdateEstadoFreImpSev('sys_severidad', $data['sys_severidad']);
            if ($textSeveridad) {
                $newAttributes['sys_severidad'] = $data['sys_severidad'];
                $textField[]                    = "Severidad $textSeveridad";
            }
        }

        if (!$newAttributes) {
            return;
        }

        $SaveFt = new SaveFt($this->getDocument());
        $SaveFt->edit($newAttributes);
        $this->Model = $this->getDocument()->getFt();

        if ($refreshDescription) {
            $this->getDocument()->refreshDescription();
        }


        $text          = "Se actualiza: ".implode(', ', $textField);
        $newType       = $this->getModel()->getFieldValue(PqrFormFieldEntity::FIELD_NAME_SYS_TIPO);
        $newSubType    = $this->getPqrService()->subTypeExist() ? $this->getModel()->getFieldValue('sys_subtipo') : '';
        $newDependency = $this->getPqrService()->dependencyExist() ? $this->getValueForReport(
            PqrFormFieldEntity::FIELD_NAME_SYS_DEPENDENCIA,
        ) : '';

        $text = str_replace([
            '{newType}',
            '{newSubType}',
            '{newDependency}',
        ], [
            $newType,
            $newSubType,
            $newDependency,
        ], $text);

        $history = [
            'tipo'        => PqrHistoryEntity::TIPO_CAMBIO_ESTADO,
            'descripcion' => $text,
        ];

        $this->saveHistory($history);
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
            'tipo'        => PqrHistoryEntity::TIPO_CAMBIO_ESTADO,
            'descripcion' => "Se actualiza la oportunidad en la respuesta de : $oldOportuno a $newOportuno",
        ];

        $this->saveHistory($history);

        return true;
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
            if (!$Documento->isActive() && $Documento->isAvailable()) {
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

        $now = $this->getModel()->sys_fecha_terminado ? DateController::getDateTimeFromDataBase(
            $this->getModel()->sys_fecha_terminado,
        ) : new DateTime();
        $now->setTime(0, 0);

        $expirationDate = $this->getExpirationDate();
        $now->setTime(0, 0);

        $diff = $now->diff($expirationDate);

        $color = "success";
        if ($diff->invert || $diff->days <= FtPqr::VENCIMIENTO_ROJO) {
            $color = 'danger';
        } elseif ($diff->days <= FtPqr::VENCIMIENTO_AMARILLO) {
            $color = 'warning';
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
            DateController::PUBLIC_DATE_FORMAT,
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
     *
     * @return string|null
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function getValueForReport(string $name): ?string
    {
        $field = $this->getPqrFormFieldRepository()->findByName($name);
        if (!$field) {
            return null;
        }
        $value = $this->getValue($field);

        return $value ? implode(',', $value) : null;
    }

    /**
     * @return PqrHistoryEntity[]
     */
    public function getHistory(string $order = 'id desc'): array
    {
        [$field, $dir] = array_pad(explode(' ', trim($order), 2), 2, 'DESC');

        return $this->getPqrHistoryRepository()->findBy(
            ['idft' => $this->getModel()->getPK()],
            [$field => strtoupper($dir)],
        );
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
            'documentId' => $this->getDocument()->getPK(),
        ];
        $data   = CryptController::encrypt(json_encode($params));

        return sprintf(
            "%sws/%s/infoQR.html?data=%s",
            $this->serviceLocator->domain,
            $this->getModel()->getFormat()->nombre,
            urlencode($data),
        );
    }

    /**
     * Cambia el estado de la PQR
     *
     * @param string $newStatus
     * @param string $observations
     *
     * @return void
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function changeStatus(string $newStatus, string $observations = ''): void
    {
        $actualStatus = $this->getModel()->sys_estado;

        if ($actualStatus != $newStatus) {
            $this->getModel()->sys_estado = $newStatus;

            if ($newStatus == FtPqr::ESTADO_TERMINADO) {
                $this->getModel()->sys_fecha_terminado = date('Y-m-d H:i:s');
                $this->getModel()->sys_oportuno        = $this->getRespuestaOportuna();
            } else {
                $this->getModel()->sys_fecha_terminado = null;
                $this->getModel()->sys_oportuno        = $this->getRespuestaOportuna();
                $this->getModel()->setSaveNullAttributes(true);
            }
            $this->getModel()->save();

            $this->saveHistory([
                'tipo'        => PqrHistoryEntity::TIPO_CAMBIO_ESTADO,
                'descripcion' => "Se actualiza el estado de la solicitud de $actualStatus a $newStatus. $observations",
            ]);
        }
    }

    /**
     * @param string $fieldName
     * @param        $value
     *
     * @return string|null
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2021-10-05
     */
    protected function getTextForUpdateEstadoFreImpSev(string $fieldName, $value): ?string
    {
        if ($value == $this->getModel()->$fieldName) {
            return null;
        }

        $newValue = $this->getModel()->getValueLabel($fieldName, $value);
        if (!$this->getModel()->$fieldName) {
            $text = "a $newValue";
        } else {
            $oldType = $this->getModel()->getValueLabel($fieldName);
            $text    = "de $oldType a $newValue";
        }

        return $text;
    }

    /**
     * @param string $expirationDate
     *
     * @return string|null
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2021-10-05
     */
    protected function getTextForUpdateExpirationDate(string $expirationDate): ?string
    {
        $expiration = $this->getExpirationDate();
        if ($expirationDate == $expiration->format('Y-m-d')) {
            return null;
        }

        $oldDate = $expiration->format(DateController::PUBLIC_DATE_FORMAT);

        $newDate = DateController::convertDate(
            $expirationDate,
            DateController::PUBLIC_DATE_FORMAT,
            'Y-m-d',
        );

        return "fecha de vencimiento de $oldDate a $newDate";
    }

    /**
     * Actualiza la fecha de vencimiento
     *
     * @return bool
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

            $this->saveHistory([
                'tipo'        => PqrHistoryEntity::TIPO_CAMBIO_VENCIMIENTO,
                'descripcion' => "Se actualiza la fecha de vencimiento a ".
                    $DateTimeForType->format(DateController::PUBLIC_DATE_FORMAT),
            ]);
        }

        return true;
    }

    /**
     * Registra la distribucion
     *
     * @return void
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function saveDistribution(): void
    {
        if ($this->getDocument()->fromWebservice()) {
            return;
        }

        $option = (int)$this->getModel()->getKeyField(Distribution::SELECT_MENSAJERIA);

        switch ($option) {
            case FtPqrRespuesta::DISTRIBUCION_RECOGIDA_ENTREGA:
                //                $recogida = DistributionService::ESTADO_RECOGIDA;
                //                $estado = DistributionService::DISTRIBUCION_POR_RECEPCIONAR;
                //                break;

            case FtPqrRespuesta::DISTRIBUCION_SOLO_ENTREGA:
                $recogida = DistributionService::ESTADO_ENTREGA;
                $estado   = DistributionService::DISTRIBUCION_PENDIENTE;
                break;

            case FtPqrRespuesta::DISTRIBUCION_NO_REQUIERE_MENSAJERIA:
            case FtPqrRespuesta::DISTRIBUCION_ENVIAR_EMAIL:
                $recogida = DistributionService::ESTADO_ENTREGA;
                $estado   = DistributionService::DISTRIBUCION_FINALIZADA;
                break;

            default:
                throw new ValidationFailedException(
                    $this->serviceLocator->getTranslator(
                    )->trans('tipo_distribucion_no_definida'),
                );
        }
        $DistributionService = new DistributionService($this->getModel()->getDocument());
        $fieldName           = Distribution::DESTINO_INTERNO;

        $DistributionService->start(
            $this->getModel()->sys_tercero,
            DistributionService::TIPO_EXTERNO,
            $this->getModel()->$fieldName,
            DistributionService::TIPO_INTERNO,
            $estado,
            $recogida,
        );
    }

    protected function sendNotificationToInternalDestination(): void
    {
        $FuncionarioDesInt = $this->getModel()->getFuncionarioDestinoInterno();
        if (!$FuncionarioDesInt) {
            return;
        }

        $TareaService = (new Tarea())->getService();
        if (!$TareaService->createOrUpdate($this->getTaskDefaultData())) {
            throw new InvalidArgumentException(
                $this->serviceLocator->getTranslator(
                )->trans('no_fue_posible_crear_tarea'),
            );
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
            'nombre'        => 'Resolver PQR # '.$this->getDocument()->numero,
            'managers'      => [
                [
                    'id'       => $FuncionarioDesInt->getPK(),
                    'external' => 0,
                ],
            ],
            'notification'  => $this->enableEmailNotificationTask(),// Notificar por Email
            'fecha_inicial' => $this->getTaskDefaultStartDate($DateTime),
            'fecha_final'   => $this->getTaskDefaultEndDate($DateTime),
            'descripcion'   => '',
            'relacion'      => Tarea::RELACION_DOCUMENTO,
            'relacion_id'   => $this->getDocument()->getPK(),
            'permitir_edit' => 0,
        ];
    }

    /**
     * Uno para enviar correo de la tarea y 0 para no enviar
     *
     * @return int
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2024-02-20
     */
    protected function enableEmailNotificationTask(): int
    {
        return 0;
    }

    /**
     * @param DateTime $DateTime
     *
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2023-05-15
     */
    protected function getTaskDefaultStartDate(DateTime $DateTime): string
    {
        return $DateTime->format('Y-m-d H:i:s');
    }

    /**
     * @param DateTime $DateTime
     *
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
     *
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2023-06-23
     */
    protected function getRespuestaOportuna(?string $date = null): string
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
     *
     * @return void
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2023-06-26
     */
    protected function saveHistory(array $data): void
    {
        $history = array_merge([
            'idft'           => $this->getModel()->getPK(),
            'fk_funcionario' => $this->serviceLocator->getSecurity()->getUser()->getId(),
            'idfk'           => 0,
        ], $data);

        $this->getPqrHistoryService()->create($history);
    }

    /**
     * Obtiene el funcionario a quien se le asignara la PQR
     *
     * @param Grupo $Grupo
     *
     * @return VfuncionarioDc|null
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2024-02-20
     */
    protected function getFuncionarioFromGroup(Grupo $Grupo): ?VfuncionarioDc
    {
        if (!$Grupo->estado) {
            return null;
        }

        $arrGrupoFunc = $this->getUserForGroup($Grupo);
        if (!$arrGrupoFunc) {
            return null;
        }

        $arraFunc = [];
        foreach ($arrGrupoFunc as $row) {
            $cantTask                               = $this->getTaskForUser($row['idfuncionario']);
            $arraFunc[$row['fk_dependencia_cargo']] = $cantTask;
        }

        $minValue           = min($arraFunc);
        $fKdependenciaCargo = array_search($minValue, $arraFunc);

        return VfuncionarioDc::findByRole($fKdependenciaCargo);
    }

    /**
     * Obtiene los funcionarios asignados al grupo
     *
     * @param Grupo $Grupo
     *
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2024-02-20
     */
    protected function getUserForGroup(Grupo $Grupo): array
    {
        return $this->serviceLocator
            ->getConnection()
            ->createQueryBuilder()
            ->select('dc.funcionario_idfuncionario as idfuncionario,gf.fk_dependencia_cargo')
            ->from('grupo_funcionario', 'gf')
            ->join('gf', 'dependencia_cargo', 'dc', 'dc.iddependencia_cargo=gf.fk_dependencia_cargo')
            ->where('gf.estado=1')
            ->andWhere('dc.estado=1')
            ->andWhere('gf.fk_grupo=:groupId')
            ->setParameter('groupId', $Grupo->getPK(), ParameterType::INTEGER)
            ->executeQuery()->fetchAllAssociative();
    }

    /**
     * Obtiene las tareas por funcionario asignadas a los documentos
     * del formato PQR
     *
     * @param int $idfuncionario
     *
     * @return int
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2024-02-20
     */
    protected function getTaskForUser(int $idfuncionario): int
    {
        return (int)$this->serviceLocator
            ->getConnection()
            ->createQueryBuilder()
            ->select('cant_task')
            ->from('vpqr_tareas')
            ->where('idfuncionario=:idfuncionario')
            ->setParameter('idfuncionario', $idfuncionario, ParameterType::INTEGER)
            ->executeQuery()->fetchOne();
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return $this->serviceLocator->getEntityManager();
    }

    private function getPqrFormRepository(): PqrFormRepository
    {
        return $this->getEntityManager()->getRepository(PqrFormEntity::class);
    }

    private function getPqrNotificationRepository(): PqrNotificationRepository
    {
        return $this->getEntityManager()->getRepository(PqrNotificationEntity::class);
    }

    private function getPqrBackupRepository(): PqrBackupRepository
    {
        return $this->getEntityManager()->getRepository(PqrBackupEntity::class);
    }

    private function getPqrBalancerRepository(): PqrBalancerRepository
    {
        return $this->getEntityManager()->getRepository(PqrBalancerEntity::class);
    }

    private function getPqrFormFieldRepository(): PqrFormFieldRepository
    {
        return $this->getEntityManager()->getRepository(PqrFormFieldEntity::class);
    }

    private function getPqrHistoryRepository(): PqrHistoryRepository
    {
        return $this->getEntityManager()->getRepository(PqrHistoryEntity::class);
    }

    private function getPqrNotyMessageRepository(): PqrNotyMessageRepository
    {
        return $this->getEntityManager()->getRepository(PqrNotyMessageEntity::class);
    }

    private function getPqrResponseTimeRepository(): PqrResponseTimeRepository
    {
        return $this->getEntityManager()->getRepository(PqrResponseTimeEntity::class);
    }

    private function buildPdfUrl(): string
    {
        $documento = $this->getDocument();
        try {
            $publicPath = $documento->getPdfFile()->getPublicTemporalPath();

            return $this->serviceLocator->domain.$publicPath;
        } catch (Throwable $th) {
            $this->serviceLocator->getLogger()->error($th->getMessage(), [
                'documentId' => $documento->getPK(),
                'trace'      => $th->getTraceAsString(),
            ]);
        }

        return '#';
    }

    private function getPqrHistoryService(): PqrHistoryService
    {
        return $this->serviceLocator->get(PqrHistoryService::class);
    }

}
