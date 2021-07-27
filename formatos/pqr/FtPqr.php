<?php

namespace App\Bundles\pqr\formatos\pqr;

use App\services\exception\SaiaException;
use Saia\models\Tercero;
use Saia\controllers\DateController;
use App\Bundles\pqr\helpers\UtilitiesPqr;
use App\Bundles\pqr\Services\FtPqrService;
use App\Bundles\pqr\Services\models\PqrBackup;
use App\Bundles\pqr\Services\models\PqrFormField;
use App\Bundles\pqr\formatos\pqr_respuesta\FtPqrRespuesta;

class FtPqr extends FtPqrProperties
{
    const ESTADO_PENDIENTE = 'PENDIENTE';
    const ESTADO_PROCESO = 'PROCESO';
    const ESTADO_TERMINADO = 'TERMINADO';

    const VENCIMIENTO_ROJO = 1; //DIAS
    const VENCIMIENTO_AMARILLO = 5; //DIAS

    private ?FtPqrService $FtPqrService = null;


    /**
     * @return PqrBackup
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2021-05-28
     */
    public function getPqrBackup(): PqrBackup
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
        return true;
    }

    /**
     * Obtiene la url del Qr
     *
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2021-05-13
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
        if (
            !$this->getService()->createBackup() ||
            !$this->getService()->updateFechaVencimiento()
        ) {
            throw new SaiaException($this->getService()->getErrorManager()->getMessage(), 200);
        }

        return $this->getService()->createTercero();
    }

    /**
     * @inheritDoc
     */
    public function afterRad(): bool
    {
        return $this->getService()->sendNotifications() &&
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
    private function setDefaultValues(): void
    {
        $this->sys_estado = self::ESTADO_PENDIENTE;
        $this->sys_fecha_vencimiento = null;
        $this->sys_fecha_terminado = null;
        $this->save();
    }


    /**
     * Carga todo el mostrar del formulario
     *
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function showContent(): string
    {
        $Qr = UtilitiesPqr::showQr($this);

        $fecha = DateController::convertDate($this->getDocument()->fecha, 'Ymd');
        $text = sprintf(
            '%s %s-%s',
            'Radicado:',
            $fecha,
            $this->getDocument()->numero
        );

        $labelPQR = mb_strtoupper($this->getService()->getPqrForm()->label, 'UTF-8');

        $data = $this->getPqrBackup()->getDataJson();

        $trs = '';
        foreach ($data as $key => $value) {
            $trs .= '<tr>
                <td style="width:50%"><strong>' . mb_strtoupper($key, 'UTF-8') . '</strong></td>
                <td style="width:50%">' . $value . '</td>
            </tr>';
        }

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
            $trs
        </table>
HTML;

    }

    /**
     * Carga el HTML del adicionar/editar para los campos
     *  AutompleteD
     *
     * @param integer $idCamposFormato
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function autocompleteD(int $idCamposFormato): string
    {
        $PqrFormField = PqrFormField::findByAttributes([
            'fk_campos_formato' => $idCamposFormato
        ]);
        return $this->getService()->generateField($PqrFormField);
    }

    /**
     * Carga el HTML del adicionar/editar para los campos
     *  Automplete
     *
     * @param integer $idCamposFormato
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function autocompleteM(int $idCamposFormato): string
    {
        $PqrFormField = PqrFormField::findByAttributes([
            'fk_campos_formato' => $idCamposFormato
        ]);

        return $this->getService()->generateField($PqrFormField);
    }

}
