<?php

namespace App\Bundles\pqr\Services\controllers;

use Saia\models\Configuracion;
use App\Bundles\pqr\Services\models\PqrHistory;
use App\Bundles\pqr\formatos\pqr\FtPqr;
use Saia\controllers\DateController;
use Saia\models\documento\Documento;
use Saia\controllers\anexos\FileJson;
use Saia\controllers\CryptController;
use Saia\controllers\functions\Header;

use Saia\controllers\TemporalController;
use App\Bundles\pqr\helpers\UtilitiesPqr;

class FtPqrController extends Controller
{


    /**
     * Obtiene los datos de la PQR
     *
     * @return object
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function index(): object
    {
        $Response = (object) [
            'success' => 0,
            'data' => []
        ];
        if ($id = $this->request['id']) {
            if ($FtPqr = FtPqr::findByDocumentId($id)) {
                $Response->success = 1;
                $Response->data = $FtPqr->getDataAttributes();
            }
        }

        return $Response;
    }

    /**
     * Obtiene el email
     *
     * @return object
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function getEmail(): object
    {
        $Response = (object) [
            'success' => 0,
            'data' => []
        ];
        if ($id = $this->request['id']) {
            if ($FtPqr = FtPqr::findByDocumentId($id)) {
                $Response->success = 1;
                $Response->data = $FtPqr->sys_email;
            }
        }

        return $Response;
    }

    /**
     * Obtiene el email
     *
     * @return object
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     * 
     */
    public function getHistoryForTimeLine(): object
    {
        $Response = (object) [
            'success' => 0,
            'data' => []
        ];

        $data = json_decode(CryptController::decrypt($this->request['infoCryp']));
        $FtPqr = FtPqr::findByDocumentId($data->documentId);

        if ($FtPqr->getPK() != $data->id) {
            throw new \Exception("La URL ingresada NO existe o ha sido eliminada", 200);
        }

        $rows = [];
        $records = $FtPqr->getHistory('fecha asc');
        $expirationDate = $this->getExpirationDate($FtPqr);
        $addExpiration = false;

        $rows[] = $this->getInitialRequestData($FtPqr->Documento);

        foreach ($records as $PqrHistory) {
            $action = DateController::convertDate($PqrHistory->fecha, 'Y-m-d');
            $actionDate = new \DateTime($action);

            if ($actionDate > $expirationDate && !$addExpiration) {
                $rows[] =  $this->getDataFinish($FtPqr);
                $addExpiration = true;
            }

            if ($row = $this->getDataHistory($PqrHistory)) {
                $rows[] = $row;
            }
        }

        if (!$addExpiration) {
            $rows[] = $this->getDataFinish($FtPqr);
        }


        $Response->data = $rows;
        $Response->success = 1;

        return $Response;
    }


    /**
     * Obtiene la fecha de expiracion
     *
     * @param Ftpqr $FtPqr
     * @return \DateTime
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    private function getExpirationDate(Ftpqr $FtPqr): \DateTime
    {
        $expiration = DateController::convertDate($FtPqr->sys_fecha_vencimiento, 'Y-m-d');

        return new \DateTime($expiration);
    }

    /**
     * Obtiene los datoss de finalizacion de timeline
     *
     * @param Ftpqr $FtPqr
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    private function getDataFinish(Ftpqr $FtPqr): array
    {
        $type = $FtPqr->getFieldValue('sys_tipo');
        return [
            'iconPoint' => 'fa fa-flag-checkered',
            'iconPointColor' => 'success',
            'date' => DateController::convertDate(
                $this->getExpirationDate($FtPqr),
                DateController::PUBLIC_DATE_FORMAT
            ),
            'description' => "Fecha maxima para dar respuesta a la solicitud de tipo {$type}"
        ];
    }

    /**
     * Retonar la informacion inicial de la solicitud para el de timeline
     *
     * @param Documento $Documento
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    private function getInitialRequestData(Documento $Documento): array
    {
        return [
            'iconPoint' => 'fa fa-map-marker',
            'iconPointColor' => 'success',
            'date' => DateController::convertDate($Documento->fecha),
            'description' => "Se registra la solicitud No # {$Documento->numero}",
            'url' => $this->getRoutePdf($Documento)
        ];
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
     * Obtiene la ruta del PDF
     *
     * @param Documento $Documento
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     * 
     * @throws Exception
     */
    private function getRoutePdf(Documento $Documento): string
    {
        try {
            if (!$Documento->pdf) {
                $Documento->getPdfJson(true);
            }

            $Object = TemporalController::createTemporalFile($Documento->pdf, '', true);
            if ($Object->success) {
                return ABSOLUTE_SAIA_ROUTE . $Object->route;
            }
        } catch (\Throwable $th) {
            $log = [
                'errorMessage' => $th->getMessage(),
                'iddocumento' => $Documento->getPK()
            ];
            $message = "No se ha podido generar el pdf del documento con radicado: {$Documento->numero} (ID:{$Documento->getPK()})";
            UtilitiesPqr::notifyAdministrator($message, $log);
        }

        return '#';
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
            $Configuracion = Configuracion::findByNames(['nombre'])[0];
            if ($Configuracion) {
                $this->customerName = $Configuracion->getValue();
            }
        }

        return $this->customerName;
    }

    /**
     * Obtiene los datos de historial para pintar el timeline
     *
     * @param PqrHistory $PqrHistory
     * @return arra|null
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    private function getDataHistory(PqrHistory $PqrHistory): ?array
    {
        $data = [
            'header' => true,
            'imgRoute' => $this->getLogo(),
            'userName' => $PqrHistory->Funcionario->getName(),
            'business' => $this->getCustomerName(),
            'date' => $PqrHistory->getFecha(),
            'description' => $PqrHistory->descripcion
        ];

        switch ($PqrHistory->tipo) {
            case PqrHistory::TIPO_RESPUESTA:
                $FtPqrRespuesta = $PqrHistory->getRespuestaPqr();
                $data = array_merge($data, [
                    'iconPoint' => 'fa fa-envelope-o',
                    'iconPointColor' => 'warning',
                    'url' => $this->getRoutePdf($FtPqrRespuesta->Documento)
                ]);
                break;

            case PqrHistory::TIPO_CALIFICACION:
                $FtPqrRespuesta = $PqrHistory->getRespuestaPqr();
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

    public static function resolveVariables(
        string $baseContent,
        FtPqr $FtPqr
    ): string {
        $functions = Header::getFunctionsFromString($baseContent);
        $functions = str_replace(['{*', '*}'], '', $functions);

        foreach ($functions as $variable) {
            $value = call_user_func([FtPqrController::class, $variable], $FtPqr);
            $baseContent = str_replace(
                "{*{$variable}*}",
                $value,
                $baseContent
            );
        }
        return $baseContent;
    }

    public static function n_numeroPqr(FtPqr $FtPqr)
    {
        return $FtPqr->Documento->numero;
    }

    public static function n_nombreFormularioPqr(FtPqr $FtPqr)
    {
        return $FtPqr->PqrForm->label;
    }
}
