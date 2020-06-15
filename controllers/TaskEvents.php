<?php

namespace Saia\Pqr\controllers;

use Saia\core\model\Model;
use Saia\models\tarea\Tarea;
use Saia\Pqr\formatos\pqr\FtPqr;
use Saia\Pqr\Helpers\UtilitiesPqr;
use Saia\models\documento\Documento;
use Saia\controllers\SessionController;
use Saia\models\tarea\IExternalEventsTask;
use Saia\Pqr\models\PqrHistory;

class TaskEvents implements IExternalEventsTask
{
    private $Tarea;
    private $Instance;
    private $nameFuncionario;

    public function __construct(Model $Instance, Tarea $Tarea)
    {
        $this->Tarea = $Tarea;
        $this->Instance = $Instance;
        $this->nameFuncionario = SessionController::getUser()->getName();
    }

    public function afterCreateTarea(): bool
    {
        return true;
    }

    public function afterUpdateTarea(): bool
    {
        if (!$this->Tarea->estado) {
            if ($DocumentoTarea = $this->Tarea->getDocument()) {
                $history = [
                    'fecha' => date('Y-m-d H:i:s'),
                    'idft' => $DocumentoTarea->Documento->getFt()->getPK(),
                    'nombre_funcionario' => $this->nameFuncionario,
                    'descripcion' => "Se elimina la tarea ({$this->Tarea->nombre})"
                ];
                if (!PqrHistory::newRecord($history)) {
                    throw new \Exception("No fue posible guardar el historial de la eliminación de la tarea", 200);
                }
                $this->updateEstado($DocumentoTarea->Documento);
            }
        }
        return true;
    }

    public function afterDeleteTarea(): bool
    {
        if ($DocumentoTarea = $this->Tarea->getDocument()) {
            $history = [
                'fecha' => date('Y-m-d H:i:s'),
                'idft' => $DocumentoTarea->Documento->getFt()->getPK(),
                'nombre_funcionario' => $this->nameFuncionario,
                'descripcion' => "Se elimina la tarea ({$this->Tarea->nombre})"
            ];
            if (!PqrHistory::newRecord($history)) {
                throw new \Exception("No fue posible guardar el historial de la eliminación de la tarea", 200);
            }

            $this->updateEstado($DocumentoTarea->Documento);
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
        if ($DocumentoTarea = $this->Tarea->getDocument()) {
            $history = [
                'fecha' => date('Y-m-d H:i:s'),
                'idft' => $DocumentoTarea->Documento->getFt()->getPK(),
                'nombre_funcionario' => $this->nameFuncionario,
                'descripcion' => "Se actualiza el estado de la tarea ({$this->Tarea->nombre}) a ({$this->Instance->getValueLabel('valor')})"
            ];
            if (!PqrHistory::newRecord($history)) {
                throw new \Exception("No fue posible guardar el historial del cambio", 200);
            }
            $this->updateEstado($DocumentoTarea->Documento);
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
            'nombre_funcionario' => $this->nameFuncionario,
            'descripcion' => "Se crea la tarea ({$this->Tarea->nombre})"
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
        $estadoActual = $Ft->sys_estado;

        if ($estadoActual != $estado) {
            $Ft->sys_estado = $estado;
            if ($estado == FtPqr::ESTADO_TERMINADO) {
                $Ft->sys_fecha_terminado = date('Y-m-d H:i:s');
            } else {
                $Ft->sys_fecha_terminado = NULL;
            }
            $Ft->update(true);

            $history = [
                'fecha' => date('Y-m-d H:i:s'),
                'idft' => $Ft->getPK(),
                'nombre_funcionario' => $this->nameFuncionario,
                'descripcion' => "Se actualiza el estado de PQRSF de ({$estadoActual}) a ({$estado})"
            ];
            if (!PqrHistory::newRecord($history)) {
                throw new \Exception("No fue posible guardar el historial del cambio", 200);
            }
        }
        return true;
    }
}
