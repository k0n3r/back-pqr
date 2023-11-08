<?php

namespace App\Bundles\pqr\formatos\pqr_respuesta;

use App\Bundles\pqr\helpers\UtilitiesPqr;
use App\Bundles\pqr\Services\FtPqrRespuestaService;
use Exception;
use Saia\controllers\localidad\MunicipioService;
use Saia\models\anexos\Anexos;
use Saia\models\Tercero;
use Saia\models\localidades\Municipio;
use App\Bundles\pqr\formatos\pqr\FtPqr;
use Saia\models\formatos\CamposFormato;
use Saia\controllers\functions\CoreFunctions;
use App\Bundles\pqr\Services\models\PqrHistory;
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

    private ?FtPqrRespuestaService $FtPqrRespuestaService = null;


    /**
     * @return FtPqr
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2021-05-28
     */
    public function getFtPqr(): FtPqr
    {
        if (!$this->FtPqr) {
            $this->FtPqr = UtilitiesPqr::getInstanceForFtId($this->ft_pqr);
        }

        return $this->FtPqr;
    }

    /**
     * @return null|Tercero
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2021-05-28
     */
    public function getTercero(): ?Tercero
    {
        if (!$this->Tercero && $this->destino) {
            $this->Tercero = new Tercero($this->destino);
        }

        return $this->Tercero;
    }

    /**
     * @return Municipio
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2021-05-28
     */
    public function getMunicipio(): Municipio
    {
        if (!$this->Municipio) {
            $this->Municipio = new Municipio($this->ciudad_origen);
        }

        return $this->Municipio;
    }

    /**
     * @return FtPqrCalificacion[]
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2021-05-28
     */
    public function getFtPqrCalificaciones(): array
    {
        return FtPqrCalificacion::findAllByAttributes([
            'ft_pqr_respuesta' => $this->getPK()
        ]);
    }

    /**
     * @inheritDoc
     */
    public function afterAdd(): bool
    {
        if ($this->getService()->sendByEmail()) {
            if (!$this->getService()->validEmails()) {
                throw new Exception(
                    $this->getService()->getErrorManager()->getMessage(),
                    $this->getService()->getErrorManager()->getCode()
                );
            }
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function afterEdit(): bool
    {
        if ($this->getService()->sendByEmail()) {
            if (!$this->getService()->validEmails()) {
                throw new Exception(
                    $this->getService()->getErrorManager()->getMessage(),
                    $this->getService()->getErrorManager()->getCode()
                );
            }
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function afterRad(): bool
    {
        $description = "Se genera la respuesta con radicado # {$this->getDocument()->numero}";
        $tipo = PqrHistory::TIPO_RESPUESTA;

        if (
            !$this->getService()->saveHistory($description, $tipo) ||
            !$this->getService()->saveDistribution() ||
            !$this->getService()->notifyEmail()
        ) {
            throw new Exception(
                $this->getService()->getErrorManager()->getMessage(),
                $this->getService()->getErrorManager()->getCode()
            );
        }

        return $this->getService()->transferCopiaInterna();

    }

    /**
     * Retorna el Servicio
     *
     * @return FtPqrRespuestaService
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2021
     */
    public function getService(): FtPqrRespuestaService
    {
        if (!$this->FtPqrRespuestaService) {
            $this->FtPqrRespuestaService = new FtPqrRespuestaService($this);
        }

        return $this->FtPqrRespuestaService;
    }

    /**
     * Genera el HTML para seleccionar la ciudad de origen
     *
     * @param CamposFormato $CamposFormato
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function selectCity(CamposFormato $CamposFormato): string
    {
        $options = '';
        if ($this->ciudad_origen) {
            $data = MunicipioService::getCityByIdForAutocomplete($this->ciudad_origen);
            $options = "<option value='{$data['id']}'>{$data['text']}</option>";
        }

        return <<<HTML
        <div class='form-group form-group-default form-group-default-select2 required' id='group_$CamposFormato->nombre'>
            <label title='Ciudad origen' class='autocomplete'>$CamposFormato->etiqueta</label>
            <select class="full-width required" id='ciudad_origen' name='$CamposFormato->nombre'>
            $options
            </select>
        </div>
HTML;

    }

    /**
     * Genera el HTML para checkear la solicitud de encuesta
     *
     * @param CamposFormato $CamposFormato
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function fieldSatisfactionSurvey(CamposFormato $CamposFormato): string
    {
        $check = (int)$this->sol_encuesta;
        $checked1 = $checked0 = '';
        if ($this->getPK()) {
            $checked1 = $check ? 'checked' : '';
            $checked0 = $check ? '' : 'checked';
        }

        return <<<HTML
            <div class='form-group form-group-default required'>
                <label>$CamposFormato->etiqueta</label>
                <div class='radio radio-success input-group'>
                    <input type='radio' name='$CamposFormato->nombre' id='{$CamposFormato->nombre}1' value='1' $checked1>
                    <label for='{$CamposFormato->nombre}1' class='mr-3 label-without-focus'> SI </label>
            
                    <input type='radio' name='$CamposFormato->nombre' id='{$CamposFormato->nombre}0' value='0' $checked0>
                    <label for='{$CamposFormato->nombre}0' class='mr-3 label-without-focus'> NO </label>
                </div>
                <label id='$CamposFormato->nombre-error' class='error' for='$CamposFormato->nombre' style='display: none;'></label>
            </div>
HTML;
    }


    /**
     * Show
     * Carga el mostrar de la respuesta a la PQR
     *
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function showTemplate(): string
    {
        $Qr = $this->showQr();
        $firmas = $this->showSignatures();

        return <<<HTML
            <table style="width: 100%;border: 0">
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
                        <td>{$this->getFieldValue('destino')}</td>
                        <td style="text-align:center">$Qr<br/>No.{$this->getRadicado()}</td>
                    </tr>

                    <tr>
                        <td colspan="2">&nbsp;</td>
                    </tr>
                    <tr>
                        <td colspan="2">&nbsp;</td>
                    </tr>

                    <tr>
                        <td colspan="2">ASUNTO: $this->asunto</td>
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
                    
                    <tr>
                        <td colspan="2">&nbsp;</td>
                    </tr>
                </tbody>
            </table>
            {$this->getContent()}
            <p>{$this->getDespedida()}<br/><br/></p>
            $firmas
            <p>{$this->getOtherData()}</p>
HTML;

    }

    protected function showQr()
    {
        return CoreFunctions::mostrar_qr($this);
    }

    protected function showSignatures()
    {
        return CoreFunctions::mostrar_estado_proceso($this);
    }

    protected function getContent(): string
    {
        return $this->getFieldValue('contenido') ?? '';
    }

    /**
     * Obtiene fecha y nombre de la ciudad
     *
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function getFechaCiudad(): string
    {
        return $this->getMunicipio()->nombre . ", " . strftime("%d de %B de %Y",
                strtotime($this->getDocument()->fecha));
    }

    /**
     * Obtiene el radicado
     *
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function getRadicado(): string
    {
        return $this->getDocument()->getService()->getFilingReferenceNumber();
    }

    /**
     * Obtiene el texto de despedida
     *
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function getDespedida(): string
    {
        return $this->getKeyField('despedida') == FtPqrRespuesta::OTRA_DESPEDIDA ?
            $this->otra_despedida : $this->getFieldValue('despedida');
    }

    /**
     * Obtiene mas informacion que va en el mostrar
     *
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function getOtherData(): string
    {
        $data = '';
        if ($this->anexos_fisicos) {
            $data .= "Anexos físicos: $this->anexos_fisicos<br/>";
        }

        if ($anexosDigitales = $this->getNameAnexosDigitales()) {
            $data .= "Anexos digitales: $anexosDigitales<br/>";
        }

        $dataCopy = [];
        if ((int)$this->ver_copia) {
            if ($copia = $this->getFieldValue('copia_interna')) {
                $dataCopy[] = $copia;
            }
        }

        if ($copiaExterna = $this->getNameCopiaExterna()) {
            $dataCopy[] = $copiaExterna;
        }

        if ($dataCopy) {
            $infoCopy = implode(', ', $dataCopy);
            $data .= "Con copia: $infoCopy<br/>";
        }

        $data .= "Proyectó: {$this->getCreador()}";

        return $data;
    }

    /**
     * Obtiene los nombres de los anexos
     *
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    protected function getNameAnexosDigitales(): string
    {
        $id = $this->getFormat()->getField('anexos_digitales')->getPK();

        $names = Anexos::findColumn('etiqueta', [
            'documento_iddocumento' => $this->getDocument()->getPK(),
            'campos_formato'        => $id,
            'estado'                => 1,
            'eliminado'             => 0
        ]);

        return $names ? implode(', ', $names) : '';
    }

    /**
     * Obtiene los nombres de las personas a quien va con copia externa
     *
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    protected function getNameCopiaExterna(): string
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
     * @date   2020
     */
    protected function getCreador(): string
    {
        return $this->getDocument()->getMaker()->getName();
    }

}
