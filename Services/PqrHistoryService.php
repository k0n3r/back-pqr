<?php

namespace App\Bundles\pqr\Services;

use App\services\models\ModelService;
use Saia\models\Dependencia;
use Saia\models\Configuracion;
use Saia\controllers\anexos\FileJson;
use App\Bundles\pqr\helpers\UtilitiesPqr;
use App\Bundles\pqr\Services\models\PqrHistory;

class PqrHistoryService extends ModelService
{

    /**
     * @inheritDoc
     */
    public function getModel(): PqrHistory
    {
        return $this->Model;
    }

    /**
     * Obtiene los datos de historial para pintar el timeline
     *
     * @return array|null
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function getHistoryForTimeline(): ?array
    {
        $data = [
            'header' => true,
            'imgRoute' => $this->getLogo(),
            'userName' => $this->getModel()->Funcionario->getName(),
            'business' => $this->getCustomerName(),
            'date' => $this->getModel()->getFecha(),
            'description' => $this->getModel()->descripcion
        ];

        switch ($this->getModel()->tipo) {
            case PqrHistory::TIPO_RESPUESTA:
                $FtPqrRespuesta = $this->getModel()->getRespuestaPqr();
                $data = array_merge($data, [
                    'iconPoint' => 'fa fa-envelope-o',
                    'iconPointColor' => 'warning',
                    'url' => UtilitiesPqr::getRoutePdf($FtPqrRespuesta->Documento)
                ]);
                break;

            case PqrHistory::TIPO_CALIFICACION:
                $FtPqrRespuesta = $this->getModel()->getRespuestaPqr();
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
        }

        return $data;
    }

    /**
     * Obtiene el logo de la empresa
     *
     * @return string|null
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
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
            $this->logo = $_SERVER['APP_DOMAIN'] . $FileTemporal->getRouteFromRoot();
        }

        return $this->logo;
    }

    /**
     * Obtiene el nombre del cliente
     *
     * @return string|null
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
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
