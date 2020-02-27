<?php

namespace Saia\Pqr\Controllers;

use Saia\core\model\Model;
use Saia\models\tarea\Tarea;
use Saia\models\tarea\IExternalEventsTask;

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

    public function afterCreateTareaAnexo(): void
    {
    }

    public function afterCreateTareaComentario(): void
    {
    }

    public function afterCreateTareaEstado(): void
    {
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
        // $Documento = $this->Instance->Documento;
        // $Ft = $Documento->getFt();
        // $Ft->setAttributes([
        //     'fk_tarea'
        // ]);
    }
}
