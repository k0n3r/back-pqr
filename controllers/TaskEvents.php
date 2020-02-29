<?php

namespace Saia\Pqr\Controllers;

use Saia\core\model\Model;
use Saia\models\documento\Documento;
use Saia\models\tarea\Tarea;
use Saia\Pqr\Helpers\UtilitiesPqr;
use Saia\models\tarea\IExternalEventsTask;
use Saia\Pqr\formatos\pqr\FtPqr;

class TaskEvents implements IExternalEventsTask
{
    protected $Tarea;
    protected $Instance;

    public function __construct(Model $Instance, Tarea $Tarea)
    {
        $this->Tarea = $Tarea;
        $this->Instance = $Instance;
    }

    public function afterCreateTarea(): void
    {
    }

    public function afterUpdateTarea(): void
    {
    }

    public function afterDeleteTarea(): void
    {
    }

    public function afterCreateTareaAnexo(): void
    {
    }

    public function afterCreateTareaComentario(): void
    {
    }

    public function afterCreateTareaEstado(): void
    {
        $DocumentoTarea = $this->Tarea->getDocument();
        $this->updateEstado($DocumentoTarea->Documento);
    }

    public function afterCreateTareaEtiqueta(): void
    {
    }

    public function afterCreateTareaFuncionario(): void
    {
    }

    public function afterUpdateTareaFuncionario(): void
    {
    }

    public function afterCreateTareaNotificacion(): void
    {
    }

    public function afterCreateTareaPrioridad(): void
    {
    }

    public function afterCreateDocumentoTarea(): void
    {
        $this->updateEstado($this->Instance->Documento);
    }

    /**
     * Actualiza el estado de la PQR
     *
     * @param Documento $Documento
     * @return void
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    protected function updateEstado(Documento $Documento): void
    {
        $estado = FtPqr::ESTADO_PENDIENTE;

        $data = UtilitiesPqr::getFinishTotalTask($Documento);
        if ($data['total']) {
            $estado = $data['total'] == $data['finish'] ?
                FtPqr::ESTADO_TERMINADO : FtPqr::ESTADO_PROCESO;
        }
        $Ft = $Documento->getFt();
        $estadoActual = $Ft->sys_estado;

        if ($estadoActual != $estado) {
            $Ft->sys_estado = $estado;
            $Ft->update();
        }
    }
}
