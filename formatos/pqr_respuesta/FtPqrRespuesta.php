<?php

namespace App\Bundles\pqr\formatos\pqr_respuesta;

use App\Bundles\pqr\Services\FtPqrRespuestaService;
use Exception;
use Saia\controllers\localidad\MunicipioService;
use Saia\models\formatos\CampoOpciones;
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
     * @param integer $idCamposFormato
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function selectCity(int $idCamposFormato): string
    {
        $CamposFormato = new CamposFormato($idCamposFormato);

        $options = '';
        if ($this->ciudad_origen) {
            $data = MunicipioService::getCityByIdForAutocomplete($this->ciudad_origen);
            $options = "<option value='{$data[0]['id']}'>{$data[0]['text']}</option>";
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
     * @param integer $idCamposFormato
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function fieldSatisfactionSurvey(int $idCamposFormato): string
    {

        $CamposFormato = new CamposFormato($idCamposFormato);

        $none = 'd-none';
        $check = (int)$this->sol_encuesta;
        $checked = $check ? 'checked' : '';

        if ($this->tipo_distribucion) {
            $none = (new CampoOpciones($this->tipo_distribucion))->llave != self::DISTRIBUCION_ENVIAR_EMAIL ? 'd-none' : '';
        }

        return "<div class='form-group form-group-default $none' id='group_sol_encuesta'>
            <input type='hidden' name='sol_encuesta' id='sol_encuesta' value='$check'>
            <div class='checkbox check-success input-group'>
                <input type='checkbox' id='sol_encuesta1' $checked>
                <label for='sol_encuesta1' class='mr-3'>
                    $CamposFormato->etiqueta
                </label>
            </div>
        </div>";
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
        $Qr = CoreFunctions::mostrar_qr($this);
        $firmas = CoreFunctions::mostrar_estado_proceso($this);
        $Service = $this->getService();

        return <<<HTML
            <table border="0" cellspacing="0" style="width: 100%;">
                <tbody>
                    <tr>
                        <td colspan="2">{$Service->getFechaCiudad()}</td>
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
                        <td>{$Service->getInfoDestino()}</td>
                        <td style="text-align:center">$Qr<br/>No.{$Service->getRadicado()}</td>
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
            $this->contenido
            <p>{$Service->getDespedida()}<br/><br/></p>
            $firmas
            <p>{$Service->getOtherData()}</p>
HTML;

    }

}
