<?php

namespace Saia\Pqr\controllers;

use Saia\core\model\Model;
use Saia\models\Funcionario;
use Saia\models\tarea\Tarea;
use Saia\Pqr\models\PqrHistory;
use Saia\Pqr\formatos\pqr\FtPqr;
use Saia\Pqr\helpers\UtilitiesPqr;
use Saia\models\documento\Documento;
use Saia\controllers\SessionController;
use Saia\models\tarea\IExternalEventsTask;

class TaskEvents implements IExternalEventsTask
{
    private Tarea $Tarea;
    private $Instance;
    private Funcionario $Funcionario;

    public function __construct(Model $Instance, Tarea $Tarea)
    {
        $this->Tarea = $Tarea;
        $this->Instance = $Instance;
        $this->Funcionario = SessionController::getUser();
    }

    public function afterCreateTarea(): bool
    {
        return true;
    }

    public function afterUpdateTarea(): bool
    {
        if (!$this->Tarea->estado) {
            if ($Documento = $this->Tarea->getService()->getDocument()) {
                $history = [
                    'fecha' => date('Y-m-d H:i:s'),
                    'idft' => $Documento->getFt()->getPK(),
                    'fk_funcionario' => $this->Funcionario->getPK(),
                    'tipo' => PqrHistory::TIPO_TAREA,
                    'idfk' => $this->Tarea->getPK(),
                    'descripcion' => "Se elimina la tarea: {$this->Tarea->nombre}"
                ];
                if (!PqrHistory::newRecord($history)) {
                    throw new \Exception("No fue posible guardar el historial de la eliminación de la tarea", 200);
                }
                $this->updateEstado($Documento);
            }
        }
        return true;
    }

    public function afterDeleteTarea(): bool
    {
        if ($Documento = $this->Tarea->getService()->getDocument()) {
            $history = [
                'fecha' => date('Y-m-d H:i:s'),
                'idft' => $Documento->getFt()->getPK(),
                'fk_funcionario' => $this->Funcionario->getPK(),
                'tipo' => PqrHistory::TIPO_TAREA,
                'idfk' => $this->Tarea->getPK(),
                'descripcion' => "Se elimina la tarea: {$this->Tarea->nombre}"
            ];
            if (!PqrHistory::newRecord($history)) {
                throw new \Exception("No fue posible guardar el historial de la eliminación de la tarea", 200);
            }

            $this->updateEstado($Documento);
        }
        return true;
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
                'descripcion' => "Se actualiza el estado de la tarea de {$this->Tarea->nombre} a {$this->Instance->getValueLabel('valor')}"
            ];
            if (!PqrHistory::newRecord($history)) {
                throw new \Exception("No fue posible guardar el historial del cambio", 200);
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

    public function afterCreateDocumentoTarea(): bool
    {
        $history = [
            'fecha' => date('Y-m-d H:i:s'),
            'idft' => $this->Instance->Documento->getFt()->getPK(),
            'fk_funcionario' => $this->Funcionario->getPK(),
            'tipo' => PqrHistory::TIPO_TAREA,
            'idfk' => $this->Tarea->getPK(),
            'descripcion' => "Se crea la tarea: {$this->Tarea->nombre}"

        ];
        if (!PqrHistory::newRecord($history)) {
            throw new \Exception("No fue posible guardar el historial del cambio", 200);
        }

        $this->updateEstado($this->Instance->Documento, FtPqr::ESTADO_PROCESO);
        return true;
    }

    /**
     * Actualiza el estado de la PQR
     *
     * @param Documento $Documento
     * @return void
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    protected function updateEstado(Documento $Documento, ?string $estado = null): bool
    {
        $estado = $estado ?? FtPqr::ESTADO_PENDIENTE;

        $data = UtilitiesPqr::getFinishTotalTask($Documento);
        if ($data['total']) {
            $estado = $data['total'] == $data['finish'] ?
                FtPqr::ESTADO_TERMINADO : FtPqr::ESTADO_PROCESO;
        }
        $Ft = $Documento->getFt();
        $Ft->changeStatus($estado);

        return true;
    }
}
