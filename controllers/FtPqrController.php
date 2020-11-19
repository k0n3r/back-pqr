<?php

namespace Saia\Pqr\controllers;

use Saia\models\Dependencia;
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
    private $subTypeExist;
    private $dependencyExist;

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
     * Obtiene los valores que se cargan en el modal
     * de los tipos/subtipos/fecha vencimiento/dependencia
     *
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function getDataForEditTypes(): array
    {
        $subType = $this->getSubTypes();

        $records = (CamposFormato::findByAttributes([
            'nombre' => 'sys_tipo',
            'formato_idformato' => $this->PqrForm->fk_formato
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
            'dataType' => $data,
            'dataSubType' => $subType ?? [],
            'activeDependency' => (int) $this->dependencyExist()
        ];
    }

    /**
     * Obtiene los valores que se cargan en el modal
     * de los tipos/subtipos/fecha vencimiento/dependencia
     *
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function getValuesForType()
    {
        $Response = (object) [
            'success' => 0,
            'data' => []
        ];

        if ($id = $this->request['idft']) {
            $FtPqr = new FtPqr($id);
            $date = DateController::convertDate(
                $FtPqr->sys_fecha_vencimiento,
                'Y-m-d'
            );

            $idDependencia = (int) $FtPqr->sys_dependencia;
            if ($idDependencia) {
                $options = [
                    'id' => $idDependencia,
                    'text' => $FtPqr->getValueForReport('sys_dependencia')
                ];
            }

            $Response->success = 1;
            $Response->data = [
                'sys_tipo' => (int) $FtPqr->sys_tipo,
                'sys_subtipo' => $this->subTypeExist() ? (int) $FtPqr->sys_subtipo : 0,
                'sys_fecha_vencimiento' => $date,
                'sys_dependencia' => $idDependencia,
                'optionsDependency' => $options
            ];
        }

        return $Response;
    }

    /**
     * Obtiene la informacion del subtype
     *
     * @return null|array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    private function getSubTypes(): ?array
    {
        if (!$this->subTypeExist()) {
            return null;
        }

        $PqrFormField = $this->PqrForm->getRow('sys_subtipo');
        $records = $PqrFormField->CamposFormato->CampoOpciones;

        $data = [];
        foreach ($records as $CampoOpciones) {
            if ($CampoOpciones->estado) {
                $data[] = [
                    'id' => $CampoOpciones->getPK(),
                    'text' => $CampoOpciones->valor
                ];
            }
        }

        return $data;
    }

    /**
     * Verifica si el campo subtipo fue creado
     *
     * @return boolean
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    private function subTypeExist(): bool
    {
        if ($this->subTypeExist !== null) {
            return $this->subTypeExist;
        }

        $this->subTypeExist = (bool) $this->PqrForm->getRow('sys_subtipo');

        return $this->subTypeExist;
    }

    /**
     * Verifica si el campo dependencia fue creado
     *
     * @return boolean
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    private function dependencyExist(): bool
    {
        if ($this->dependencyExist !== null) {
            return $this->dependencyExist;
        }

        $this->dependencyExist = (bool) $this->PqrForm->getRow('sys_dependencia');

        return $this->dependencyExist;
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

            if ($this->subTypeExist() && !$this->request['subtype']) {
                throw new \Exception("Error faltan parametros", 200);
            }

            $FtPqr = new FtPqr($this->request['idft']);
            $newAttributes = [];
            if ($this->request['type'] != $FtPqr->sys_tipo) {
                $oldType = $FtPqr->getFieldValue('sys_tipo');
                $newAttributes['sys_tipo'] = $this->request['type'];
                $textField[] = "tipo de {$oldType} a {newType}";
            }

            if ($this->subTypeExist()) {
                if ($this->request['subtype'] != $FtPqr->sys_subtipo) {
                    $oldSubType = $FtPqr->getFieldValue('sys_subtipo');
                    $newAttributes['sys_subtipo'] = $this->request['subtype'];
                    $textField[] = "categoria/subtipo de {$oldSubType} a {newSubType}";
                }
            }

            if ($this->dependencyExist()) {
                if ($this->request['dependency'] != $FtPqr->sys_dependencia) {
                    $oldDependency = $FtPqr->getValueForReport('sys_dependencia');
                    $newAttributes['sys_dependencia'] = $this->request['dependency'];
                    $textField[] = "dependencia de {$oldDependency} a {newDependency}";
                }
            }

            $expiration = DateController::convertDate($FtPqr->sys_fecha_vencimiento, 'Y-m-d');
            if ($this->request['expirationDate'] != $expiration) {

                $newAttributes['sys_fecha_vencimiento'] = $this->request['expirationDate'];
                $FtPqr->Documento->fecha_limite = $this->request['expirationDate'];
                $FtPqr->Documento->update();

                $oldDate = DateController::convertDate(
                    $expiration,
                    DateController::PUBLIC_DATE_FORMAT,
                    'Y-m-d'
                );

                $newDate = DateController::convertDate(
                    $this->request['expirationDate'],
                    DateController::PUBLIC_DATE_FORMAT,
                    'Y-m-d'
                );
                $textField[] = "fecha de vencimiento de {$oldDate} a {$newDate}";
            }

            $SaveFt = new SaveFt($FtPqr->Documento);
            $SaveFt->edit($newAttributes);
            $FtPqr = $FtPqr->Documento->getFt();

            $text = "Se actualiza: " . implode(', ', $textField);
            $newType = $FtPqr->getFieldValue('sys_tipo');
            $newSubType = $this->subTypeExist() ? $FtPqr->getFieldValue('sys_subtipo') : '';
            $newDependency = $this->dependencyExist() ? $FtPqr->getValueForReport('sys_dependencia') : '';

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
                'fecha' => date('Y-m-d H:i:s'),
                'idft' => $FtPqr->getPK(),
                'fk_funcionario' => $this->Funcionario->getPK(),
                'tipo' => PqrHistory::TIPO_CAMBIO_ESTADO,
                'idfk' => 0,
                'descripcion' => $text
            ];
            if (!PqrHistory::newRecord($history)) {
                throw new \Exception("No fue posible guardar el historial", 200);
            }

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
