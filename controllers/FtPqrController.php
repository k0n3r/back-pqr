<?php

namespace Saia\Pqr\controllers;

use Saia\controllers\documento\SaveFt;
use Saia\controllers\SessionController;
use Saia\Pqr\formatos\pqr\FtPqr;
use Saia\core\DatabaseConnection;
use Saia\models\formatos\CamposFormato;
use Saia\Pqr\models\PqrHistory;
use Saia\Pqr\models\PqrResponseTemplate;

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
     * Obtiene el contenido de la plantilla
     *
     * @return object
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function getPlantilla(): object
    {
        $Response = (object) [
            'success' => 0,
            'data' => []
        ];
        if ($id = $this->request['id']) {
            if ($PqrResponseTemplate = new PqrResponseTemplate($id)) {
                $Response->success = 1;
                $Response->data = $PqrResponseTemplate->content;
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
}
