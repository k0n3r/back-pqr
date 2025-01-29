<?php

namespace App\Bundles\pqr\formatos\pqr;

use App\Bundles\pqr\formatos\pqr_calificacion\FtPqrCalificacion;
use App\Bundles\pqr\Services\models\PqrForm;
use App\Exception\SaiaException;
use App\services\GlobalContainer;
use Doctrine\DBAL\ParameterType;
use Saia\controllers\generator\component\Distribution;
use Saia\models\documento\Documento;
use Saia\models\formatos\CamposFormato;
use Saia\models\Tercero;
use App\Bundles\pqr\helpers\UtilitiesPqr;
use App\Bundles\pqr\Services\FtPqrService;
use App\Bundles\pqr\Services\models\PqrBackup;
use App\Bundles\pqr\Services\models\PqrFormField;
use App\Bundles\pqr\formatos\pqr_respuesta\FtPqrRespuesta;
use Saia\models\vistas\VfuncionarioDc;

class FtPqr extends FtPqrProperties
{
    const string ESTADO_PENDIENTE = 'PENDIENTE';
    const string ESTADO_INICIADO = 'INICIADO';
    const string ESTADO_PROCESO = 'PROCESO';
    const string ESTADO_TERMINADO = 'TERMINADO';

    const int VENCIMIENTO_ROJO = 1; //DIAS
    const int VENCIMIENTO_AMARILLO = 5; //DIAS

    const int ESTADO_FRE_IMP_SEV_BAJO = 1;
    const int ESTADO_FRE_IMP_SEV_MEDIO = 2;
    const int ESTADO_FRE_IMP_SEV_ALTO = 3;

    const string OPORTUNO_PENDIENTES_SIN_VENCER = 'PENDIENTES SIN VENCER';
    const string OPORTUNO_VENCIDAS_SIN_CERRAR = 'VENCIDAS SIN CERRAR';
    const string OPORTUNO_CERRADAS_A_TERMINO = 'CERRADAS A TERMINO';
    const string OPORTUNO_CERRADAS_FUERA_DE_TERMINO = 'CERRADAS FUERA DE TERMINO';

    protected ?FtPqrService $FtPqrService = null;
    private ?FtPqrCalificacion $lastFtPqrCalificacion = null;
    private ?VfuncionarioDc $FuncionarioDestinoInterno = null;

    /**
     * @inheritDoc
     */
    protected function defineMoreAttributes(): array
    {
        return [
            'labels' => [
                'sys_frecuencia' => [
                    'label'  => 'Frecuencia',
                    'values' => [
                        self::ESTADO_FRE_IMP_SEV_BAJO  => 'Bajo',
                        self::ESTADO_FRE_IMP_SEV_MEDIO => 'Medio',
                        self::ESTADO_FRE_IMP_SEV_ALTO  => 'Alto',
                    ]
                ],
                'sys_impacto'    => [
                    'label'  => 'Impacto',
                    'values' => [
                        self::ESTADO_FRE_IMP_SEV_BAJO  => 'Bajo',
                        self::ESTADO_FRE_IMP_SEV_MEDIO => 'Medio',
                        self::ESTADO_FRE_IMP_SEV_ALTO  => 'Alto',
                    ]
                ],
                'sys_severidad'  => [
                    'label'  => 'Severidad',
                    'values' => [
                        self::ESTADO_FRE_IMP_SEV_BAJO  => 'Bajo',
                        self::ESTADO_FRE_IMP_SEV_MEDIO => 'Medio',
                        self::ESTADO_FRE_IMP_SEV_ALTO  => 'Alto',
                    ]
                ]
            ]
        ];
    }

    /**
     * @inheritDoc
     */
    public static function getParamsToAddEdit(int $action, int $idft): array
    {
        $data = [];
        if (!$action) {
            $PqrForm = PqrForm::getInstance();

            $PqrFormField = $PqrForm->getRow('sys_subtipo');

            $IWsHtml = $PqrForm->getWebservicePqr();
            $data = [
                'isActiveSubType'        => (int)($PqrFormField && $PqrFormField->isActive()),
                'isEnabledAnonymous'     => (int)$PqrForm->show_anonymous,
                'fieldsWithoutAnonymous' => $IWsHtml->getFieldsWithoutAnonymous(),
                'fieldsWithAnonymous'    => $IWsHtml->getFieldsWithAnonymous(),
                'channels'               => $PqrForm->getCanalRecepcion()
            ];
        } else {
            $data['isStarted'] = (int)(new self($idft))->getDocument()->isStarted();
        }

        return $data;
    }

    /**
     * @inheritDoc
     */
    public function getNumberFolios(): int
    {
        if ($this->getDocument()->isRadicated()) {
            return parent::getNumberFolios();
        }

        return $this->sys_folios ?: 0;
    }

    /**
     * @return PqrBackup|null
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2021-05-28
     */
    public function getPqrBackup(): ?PqrBackup
    {
        if (!$this->PqrBackup) {
            $this->PqrBackup = PqrBackup::findByAttributes([
                'fk_documento' => $this->documento_iddocumento
            ]);
        }

        return $this->PqrBackup;
    }

    /**
     * @return FtPqrRespuesta[]
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2021-05-28
     */
    public function getPqrRespuestas(): array
    {
        return FtPqrRespuesta::findAllByAttributes([
            'ft_pqr' => $this->getPK()
        ]);
    }

    /**
     * Obtiene la ultima calificacion realizada sobre la PQR
     * NO se tiene encuenta la respuesta
     *
     * @return null|FtPqrCalificacion
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2022-11-10
     */
    public function getLastCalificacion(): ?FtPqrCalificacion
    {
        if (!$this->lastFtPqrCalificacion) {
            $Qb = GlobalContainer::getConnection()
                ->createQueryBuilder()
                ->select('ft.*')
                ->from('vpqr_calificacion', 'v')
                ->join('v', 'ft_pqr_calificacion', 'ft', 'v.idft=ft.idft_pqr_calificacion')
                ->where('idft_pqr = :id')
                ->setParameter('id', $this->getPK(), ParameterType::INTEGER)
                ->orderBy('v.idft', 'desc')
                ->setMaxResults(1);

            $records = FtPqrCalificacion::findByQueryBuilder($Qb);

            $this->lastFtPqrCalificacion = $records ? $records[0] : null;
        }

        return $this->lastFtPqrCalificacion;
    }

    /**
     * @return Tercero
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2021-05-28
     */
    public function getTercero(): Tercero
    {
        if (!$this->Tercero) {
            $this->Tercero = new Tercero($this->sys_tercero);
        }

        return $this->Tercero;
    }

    /**
     * @inheritDoc
     */
    public function afterAdd(): bool
    {
        $this->setDefaultValues();
        if (!$this->getService()->validSysEmail()) {
            throw new SaiaException($this->getService()->getErrorManager()->getMessage(), 200);
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function afterEdit(): bool
    {
        if (!$this->getService()->validSysEmail()) {
            throw new SaiaException($this->getService()->getErrorManager()->getMessage(), 200);
        }

        if ($this->getDocument()->isStarted()) {
            $this->sys_estado = self::ESTADO_PENDIENTE;
            $this->getDocument()->estado = Documento::APROBADO;
            $this->getDocument()->estado_aprobacion = Documento::APROBADO_LABEL;

            $this->beforeRad();
            $this->getDocument()->getPdfJson(true);
            return $this->afterRad();
        }

        return true;
    }

    /**
     * Obtiene la url del Qr
     *
     * @return string
     * @author Andres Agudelo <andres.agudelo@ceroqk.com> 2021-05-13
     */
    public function getQrContent(): string
    {
        return $this->getService()->getUrlQR();
    }

    /**
     * @inheritDoc
     */
    public function beforeRad(): bool
    {
        if ($this->getRequest()['radicacion_rapida']) {
            return true;
        }

        if (
            !$this->getService()->createBackup() ||
            !$this->getService()->updateFechaVencimiento() ||
            !$this->getService()->createTercero()
        ) {
            throw new SaiaException($this->getService()->getErrorManager()->getMessage(), 200);
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function afterRad(): bool
    {
        if ($this->getRequest()['radicacion_rapida']) {
            return true;
        }

        $this->createTaskFromDataTemp();

        $this->saveDestinoInterno();
        $this->descripcion = $this->getDocument()->getDescription();
        $this->save();

        return $this->getService()->saveDistribution() &&
            $this->getService()->sendNotifications() &&
            $this->getService()->notifyEmail();
    }

    /**
     * Retorna el Servicio
     *
     * @return FtPqrService
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2021
     */
    public function getService(): FtPqrService
    {
        if (!$this->FtPqrService) {
            $this->FtPqrService = new FtPqrService($this);
        }

        return $this->FtPqrService;
    }

    /**
     * Setea los valores por defecto
     *
     * @author Andres Agudelo <andres.agudelo@cerok.com> @date 2021-02-23
     */
    protected function setDefaultValues(): void
    {
        $this->sys_estado = ((int)$this->getRequest()['radicacion_rapida']) ? self::ESTADO_INICIADO : self::ESTADO_PENDIENTE;
        $this->sys_fecha_vencimiento = null;
        $this->sys_fecha_terminado = null;
        $this->save();
    }

    /**
     * Carga el mostrar del formulario
     *
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function showContent(): string
    {
        $Qr = UtilitiesPqr::showQr($this);

        $text = sprintf(
            '%s %s',
            'No:',
            $this->getDocument()->getService()->getFilingReferenceNumber()
        );

        $labelPQR = mb_strtoupper($this->getService()->getPqrForm()->label, 'UTF-8');
        $tr = implode('', $this->getTableRows());

        return <<<HTML
        <table class="table table-borderless" style="width:100%">';
            <tr>
                <td style="width:50%;">
                    <p>Hemos recibido su $labelPQR <br/><br/>
                        Puede hacer seguimiento en la opción CONSULTAR MI $labelPQR de nuestro sitio Web.
                    </p>
                </td>
                <td style="width:50%;text-align:center">$Qr<br/>$text</td>
            </tr>
            <tr><td colspan="2">&nbsp;</td></tr>
            $tr
        </table>
HTML;
    }

    /**
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2022-08-19
     */
    protected function getTableRows(): array
    {
        if (!$this->getPqrBackup()) {
            return [];
        }
        $data = $this->getJsonFromPqrBackup();

        $showEmpty = $this->getService()->getPqrForm()->show_empty ?? 1;

        $tr = [];
        foreach ($data as $key => $value) {

            if (!$showEmpty && $value == '') {
                continue;
            }

            $pos = strpos($key, '__');
            if ($pos !== false) {
                $key = substr($key, 0, $pos);
            }

            $tr[$key] = '<tr>
                <td style="width:50%"><strong>' . mb_strtoupper($key, 'UTF-8') . '</strong></td>
                <td style="width:50%">' . $value . '</td>
            </tr>';
        }

        return $tr;
    }

    /**
     * Carga el HTML del adicionar/editar para los campos
     *  AutompleteD
     *
     * @param CamposFormato $CamposFormato
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function autocompleteD(CamposFormato $CamposFormato): string
    {
        $PqrFormField = PqrFormField::findByAttributes([
            'fk_campos_formato' => $CamposFormato->getPK()
        ]);
        return $this->getService()->generateField($PqrFormField);
    }

    /**
     * Carga el HTML del adicionar/editar para los campos
     *  Automplete
     *
     * @param CamposFormato $CamposFormato
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function autocompleteM(CamposFormato $CamposFormato): string
    {
        $PqrFormField = PqrFormField::findByAttributes([
            'fk_campos_formato' => $CamposFormato->getPK()
        ]);

        return $this->getService()->generateField($PqrFormField);
    }

    /**
     * Obtiene el funcionario ingresado en el campo Destino Interno
     *
     * @return VfuncionarioDc|null
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2023-01-23
     */
    public function getFuncionarioDestinoInterno(): ?VfuncionarioDc
    {
        $fieldName = $this->getFieldNameDestinoInterno();
        if (!$this->$fieldName) {
            return null;
        }

        if (!$this->FuncionarioDestinoInterno) {
            $this->FuncionarioDestinoInterno = VfuncionarioDc::findByRole($this->$fieldName);
        }

        return $this->FuncionarioDestinoInterno;
    }

    private function getFieldNameDestinoInterno(): string
    {
        return Distribution::DESTINO_INTERNO;
    }

    /**
     * Obtiene el asunto por defecto que se cargara en el adicionar de la repsuesta pqr
     *
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2023-06-09
     */
    public function getDefaultSubjectForPqrRespuesta(): string
    {
        return "Respondiendo a la {$this->getFormat()->etiqueta} No {$this->getDocument()->numero}";
    }

    /**
     * Obtiene el json de los datos a mostrar en la PQR
     *
     * @return object
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2023-08-14
     */
    protected function getJsonFromPqrBackup(): object
    {
        return $this->getPqrBackup()->getDataJson();
    }

    /**
     * Guarda el funcionario que quedara como responsable de la pqr
     *
     * @return bool
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2024-02-20
     */
    public function saveDestinoInterno(): bool
    {
        if ($this->getDocument()->fromWebservice()) {
            $PqrForm = $this->getService()->getPqrService()->getPqrForm();
            if ($PqrForm->isEnableBalancer()) {
                if ($VfuncionarioDc = $this->getService()->getFuncionarioFromBalacer()) {
                    $fieldName = $this->getFieldNameDestinoInterno();

                    $this->FuncionarioDestinoInterno = $VfuncionarioDc;
                    $this->$fieldName = $VfuncionarioDc->iddependencia_cargo;
                }
            }
        }
        return true;
    }
}
