<?php

namespace Saia\Pqr\Controllers\AddEditFormat;


use Exception;
use Saia\Pqr\Models\PqrForm;
use Saia\models\formatos\Formato;
use Saia\Pqr\Controllers\AddEditFormat\TAddEditFormat;

class FtPqrRespuestaController implements IAddEditFormat
{
    use TAddEditFormat;

    protected $PqrForm;

    public function __construct(PqrForm $PqrForm)
    {
        $this->PqrForm = $PqrForm;
    }

    public function createForm(): void
    {
        if (!$Formato = Formato::findByAttributes([
            'nombre' => 'pqr_respuesta'
        ])) {
            throw new Exception("El formato de respuesta PQR no fue encontrado", 1);
        }

        $this->PqrForm->setAttributes([
            'fk_formato_r' => $Formato->getPK()
        ]);
        $this->PqrForm->update();
    }

    public function updateForm(): void
    {
    }

    public function generateForm(): void
    {
        $this->FormatGenerator($this->PqrForm->fk_formato_r);
    }
}
