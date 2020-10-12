<?php

namespace Saia\Pqr\controllers;

use Saia\models\Funcionario;
use Saia\Pqr\models\PqrForm;
use Saia\models\Configuracion;
use Saia\Pqr\models\PqrHistory;
use Saia\Pqr\formatos\pqr\FtPqr;
use Saia\core\DatabaseConnection;
use Saia\controllers\DateController;
use Saia\models\documento\Documento;
use Saia\controllers\anexos\FileJson;
use Saia\controllers\CryptController;
use Saia\controllers\documento\SaveFt;
use Saia\controllers\functions\Header;
use Saia\controllers\SessionController;
use Saia\models\formatos\CamposFormato;
use Saia\controllers\TemporalController;

class FtPqrController extends Controller
{
    private PqrForm $PqrForm;
    private Funcionario $Funcionario;

    public function __construct(array $request = null)
    {
        $this->request = $request;

        if (!$this->PqrForm = PqrForm::getPqrFormActive()) {
            throw new \Exception("No se encuentra el formulario activo", 200);
        }
        $this->Funcionario = SessionController::getUser();
    }

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
     * Obtiene los tipos de PQR
     *
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function getTypes(): array
    {
        $records = (CamposFormato::findByAttributes([
            'nombre' => 'sys_tipo'
        ]))->CampoOpciones;

        $data = [];
        foreach ($records as $CampoOpciones) {
            if ($CampoOpciones->estado) {
                $data[] = [
                    'id' => $CampoOpciones->getPK(),
                    'text' => $CampoOpciones->valor
                ];
            }
        }

        return [
            'data' => $data
        ];
    }

    /**
     * Actualiza el tipo de PQR y guarda en el historial
     *
     * @return object
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function updateType(): object
    {
        $Response = (object) [
            'success' => 0
        ];


        try {
            $Connection = DatabaseConnection::getDefaultConnection();
            $Connection->beginTransaction();

            if (!$this->request['idft'] || !$this->request['type']) {
                throw new \Exception("Error faltan parametros", 200);
            }

            $FtPqr = new FtPqr($this->request['idft']);

            if ($this->request['type'] == $FtPqr->sys_tipo) {
                throw new \Exception("Seleccione otro estado diferente al actual", 200);
            }

            $oldStatus = $FtPqr->getFieldValue('sys_tipo');

            $SaveFt = new SaveFt($FtPqr->Documento);
            $SaveFt->edit(['sys_tipo' => $this->request['type']]);
            $FtPqr = $FtPqr->Documento->getFt();

            $newStatus = $FtPqr->getFieldValue('sys_tipo');

            $history = [
                'fecha' => date('Y-m-d H:i:s'),
                'idft' => $FtPqr->getPK(),
                'fk_funcionario' => $this->Funcionario->getPK(),
                'tipo' => PqrHistory::TIPO_CAMBIO_ESTADO,
                'idfk' => 0,
                'descripcion' => "Se actualiza el tipo de {$this->PqrForm->label} de {$oldStatus} a {$newStatus}"
            ];
            if (!PqrHistory::newRecord($history)) {
                throw new \Exception("No fue posible guardar el historial", 200);
            }
            $FtPqr->updateFechaVencimiento();

            $Response->success = 1;
            $Connection->commit();
        } catch (\Exception $th) {
            $Connection->rollBack();
            $Response->message = $th->getMessage();
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
     * Termina una PQR
     *
     * @return object
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function finish(): object
    {
        $Response = (object) [
            'success' => 0
        ];

        try {
            $Connection = DatabaseConnection::getDefaultConnection();
            $Connection->beginTransaction();

            if (!$this->request['idft'] || !$this->request['observaciones']) {
                throw new \Exception("Error faltan parametros", 200);
            }

            $FtPqr = new FtPqr($this->request['idft']);
            $Response->success = (int) $FtPqr->changeStatus(
                FtPqr::ESTADO_TERMINADO,
                $this->request['observaciones']
            );

            $Connection->commit();
        } catch (\Exception $th) {
            $Connection->rollBack();
            $Response->message = $th->getMessage();
        }

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
     */
    private function getRoutePdf(Documento $Documento): string
    {
        $Object = TemporalController::createTemporalFile($Documento->pdf, '', true);
        if ($Object->success) {
            return ABSOLUTE_SAIA_ROUTE . $Object->route;
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
