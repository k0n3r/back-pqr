<?php

namespace App\Bundles\pqr\Services\controllers;

use Exception;
use Saia\core\model\Model;
use Saia\models\Funcionario;
use Saia\models\tarea\Tarea;
use App\Bundles\pqr\Services\models\PqrHistory;
use App\Bundles\pqr\formatos\pqr\FtPqr;
use App\Bundles\pqr\helpers\UtilitiesPqr;
use Saia\models\documento\Documento;
use Saia\controllers\SessionController;
use Saia\models\tarea\IExternalEventsTask;

class TaskEvents implements IExternalEventsTask
{
    private Tarea $Tarea;
    private Model $Instance;
    private ?Funcionario $Funcionario;

    public function __construct(Model $Instance, Tarea $Tarea)
    {
        $this->Tarea = $Tarea;
        $this->Instance = $Instance;
        $this->Funcionario = SessionController::getUser();
    }

    public function afterCreateTareaAnexo(): bool
    {
        return true;
    }

    public function afterCreateTareaComentario(): bool
    {
        return true;
    }

    public function afterCreateTareaEstado(): bool
    {

        if ($Documento = $this->Tarea->getService()->getDocument()) {
            $history = [
                'fecha' => date('Y-m-d H:i:s'),
                'idft' => $Documento->getFt()->getPK(),
                'fk_funcionario' => $this->Funcionario->getPK(),
                'tipo' => PqrHistory::TIPO_TAREA,
                'idfk' => $this->Tarea->getPK(),
                'descripcion' => "Se actualiza el estado de la tarea ({$this->Tarea->nombre}) a : {$this->Instance->getValueLabel('valor')}"
            ];

            $PqrHistoryService = (new PqrHistory)->getService();
            if (!$PqrHistoryService->save($history)) {
                throw new Exception($PqrHistoryService->getErrorMessage(), 200);
            }

            $this->updateEstado($Documento);
        }
        return true;
    }

    public function afterCreateTareaEtiqueta(): bool
    {
        return true;
    }

    public function afterCreateTareaFuncionario(): bool
    {
        return true;
    }

    public function afterUpdateTareaFuncionario(): bool
    {
        return true;
    }

    public function afterCreateTareaNotificacion(): bool
    {
        return true;
    }

    public function afterCreateTareaPrioridad(): bool
    {
        return true;
    }

    /**
     * Actualiza el estado de la PQR
     *
     * @param Documento $Documento
     * @return boolean
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public static function updateEstado(Documento $Documento): bool
    {
        $estado = FtPqr::ESTADO_PENDIENTE;

        $data = UtilitiesPqr::getFinishTotalTask($Documento);
        if ($data['total']) {
            $estado = $data['total'] == $data['finish'] ?
                FtPqr::ESTADO_TERMINADO : FtPqr::ESTADO_PROCESO;
        }

        $Ft = $Documento->getFt();

        if ($estado == FtPqr::ESTADO_PENDIENTE && $Ft->sys_estado != FtPqr::ESTADO_PROCESO) {
            if ($records = $Ft->PqrRespuesta) {
                foreach ($records as $PqrRespuesta) {
                    if (!$PqrRespuesta->Documento->isDeleted()) {
                        $estado = FtPqr::ESTADO_PROCESO;
                        break;
                    }
                }
            }
        }

        return $Ft->getService()->changeStatus($estado);
    }
}
