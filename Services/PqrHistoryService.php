<?php

namespace App\Bundles\pqr\Services;

use Saia\models\Dependencia;
use Saia\models\Configuracion;
use Saia\controllers\anexos\FileJson;
use App\Bundles\pqr\helpers\UtilitiesPqr;
use App\Bundles\pqr\Services\models\PqrHistory;

class PqrHistoryService
{
    private PqrHistory $PqrHistory;
    private string $errorMessage;

    public function __construct(PqrHistory $PqrHistory)
    {
        $this->PqrHistory = $PqrHistory;
    }

    /**
     * Retorna el mensaje de error
     *
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2021
     */
    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }

    /**
     * Obtiene la instancia de PqrHistory actualizada
     *
     * @return PqrHistory
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function getModel(): PqrHistory
    {
        return $this->PqrHistory;
    }

    /**
     * Obtiene los datos de historial para pintar el timeline
     *
     * @return array|null
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function getHistoryForTimeline(): ?array
    {
        $data = [
            'header' => true,
            'imgRoute' => $this->getLogo(),
            'userName' => $this->PqrHistory->Funcionario->getName(),
            'business' => $this->getCustomerName(),
            'date' => $this->PqrHistory->getFecha(),
            'description' => $this->PqrHistory->descripcion
        ];

        switch ($this->PqrHistory->tipo) {
            case PqrHistory::TIPO_RESPUESTA:
                $FtPqrRespuesta = $this->PqrHistory->getRespuestaPqr();
                $data = array_merge($data, [
                    'iconPoint' => 'fa fa-envelope-o',
                    'iconPointColor' => 'warning',
                    'url' => UtilitiesPqr::getRoutePdf($FtPqrRespuesta->Documento)
                ]);
                break;

            case PqrHistory::TIPO_CALIFICACION:
                $FtPqrRespuesta = $this->PqrHistory->getRespuestaPqr();
                $data = array_merge($data, [
                    'iconPoint' => 'fa fa-comment',
                    'iconPointColor' => 'danger',
                    'description' => "Se solicita la calificación del servicio prestado a la respuesta # {$FtPqrRespuesta->Documento->numero}"
                ]);
                break;

            case PqrHistory::TIPO_CAMBIO_ESTADO:
            case PqrHistory::TIPO_CAMBIO_VENCIMIENTO:
                break;

            case PqrHistory::TIPO_TAREA:
            case PqrHistory::TIPO_NOTIFICACION:
            default:
                return null;
                break;
        }

        return $data;
    }

    /**
     * Obtiene el logo de la empresa
     *
     * @return string|null
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    private function getLogo(): ?string
    {
        if (!$this->logo) {
            $Configuracion = Configuracion::findByAttributes([
                'nombre' => 'logo'
            ]);

            if (!$Configuracion->getValue()) {
                return null;
            }

            $FileJson = new FileJson($Configuracion->getValue());
            $FileTemporal = $FileJson->convertToFileTemporal();
            $this->logo = ABSOLUTE_SAIA_ROUTE . $FileTemporal->getRouteFromRoot();
        }

        return $this->logo;
    }

    /**
     * Obtiene el nombre del cliente
     *
     * @return string|null
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    private function getCustomerName(): ?string
    {
        if (!$this->customerName) {
            $Dependencia = Dependencia::findByAttributes(['cod_padre' => 0]);
            if ($Dependencia) {
                $this->customerName = $Dependencia->nombre;
            }
        }

        return $this->customerName;
    }
}
