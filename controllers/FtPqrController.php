<?php

namespace Saia\Pqr\controllers;

use DateTime;
use Saia\Pqr\models\PqrHistory;
use Saia\Pqr\formatos\pqr\FtPqr;
use Saia\core\DatabaseConnection;
use Saia\controllers\CryptController;
use Saia\controllers\DateController;
use Saia\controllers\documento\SaveFt;
use Saia\controllers\SessionController;
use Saia\models\Configuracion;
use Saia\models\formatos\CamposFormato;

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
            $conn = DatabaseConnection::getDefaultConnection();
            $conn->beginTransaction();

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
                'nombre_funcionario' => SessionController::getUser()->getName(),
                'descripcion' => "Se actualiza el tipo de PQRSF de ({$oldStatus}) a ({$newStatus})"
            ];
            if (!PqrHistory::newRecord($history)) {
                throw new \Exception("No fue posible guardar el historial", 200);
            }
            $FtPqr->updateFechaVencimiento();

            $Response->success = 1;
            $conn->commit();
        } catch (\Exception $th) {
            $conn->rollBack();
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
        $records = $FtPqr->getHistory();
        $Configuracion = Configuracion::findByNames(['nombre'])[0];

        $expiration = DateController::convertDate($FtPqr->sys_fecha_vencimiento, 'Y-m-d');
        $expirationDate = new DateTime($expiration);
        $addExpiration = false;

        $rows[] = [
            'iconPoint' => 'fa fa-comment',
            'iconPointColor' => 'success',
            'iconProfile' => 'fa-2x fa fa-user',
            'business' => 'Solicitante',
            'userName' => '',
            'date' => DateController::convertDate($FtPqr->Documento->fecha),
            'description' => 'Se registra la solicitud de '
        ];

        foreach ($records as $PqrHistory) {
            $action = DateController::convertDate($PqrHistory->fecha, 'Y-m-d');
            $actionDate = new DateTime($action);

            if ($actionDate > $expirationDate && !$addExpiration) {
                $rows[] = [
                    'iconPoint' => 'fa fa-flag-checkered',
                    'iconPointColor' => 'danger',
                    'iconProfile' => 'fa-2x fa fa-users',
                    'business' => $Configuracion ? $Configuracion->getValue() : '',
                    'userName' => '',
                    'date' => DateController::convertDate($expirationDate),
                    'description' => ''
                ];
                $addExpiration = true;
            }

            $rows[] = [
                'iconPoint' => 'fa fa-users',
                'iconPointColor' => 'warning',
                'iconProfile' => 'fa-2x fa fa-users',
                'business' => $Configuracion ? $Configuracion->getValue() : '',
                'userName' => $PqrHistory->nombre_funcionario,
                'date' => DateController::convertDate($PqrHistory->fecha),
                'description' => $PqrHistory->descripcion
            ];
        }
        if (!$addExpiration) {
            $rows[] = [
                'iconPoint' => 'fa fa-flag-checkered',
                'iconPointColor' => 'danger',
                'iconProfile' => 'fa-2x fa fa-users',
                'business' => $Configuracion ? $Configuracion->getValue() : '',
                'userName' => '',
                'date' => DateController::convertDate($PqrHistory->fecha),
                'description' => ''
            ];
        }

        $Response->data = $rows;
        $Response->success = 1;

        return $Response;
    }
}
