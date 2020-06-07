<?php

namespace Saia\Pqr\controllers\services;

use Saia\Pqr\models\PqrForm;

class PqrFormService
{

    private PqrForm $PqrForm;

    public function __construct(PqrForm $PqrForm)
    {
        $this->PqrForm = $PqrForm;
    }

    /**
     * Obtiene la instancia de PqrForm actualizada
     *
     * @return PqrForm
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function getPqrForm(): PqrForm
    {
        return $this->PqrForm;
    }

    /**
     * Obtiene los campos del formulario
     *
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function getDataPqrFormFields(): array
    {
        $data = [];
        if ($records = $this->PqrForm->PqrFormFields) {
            foreach ($records as $PqrFormField) {
                $data[] = $PqrFormField->getDataAttributes();
            }
        }
        return $data;
    }

    /**
     * Obtiene los datos de construccion del formulario
     *
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function getDataPqrForm(): array
    {
        return $this->PqrForm->getDataAttributes();
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
        return $this->PqrForm->getRow('sys_tipo')->getSetting()->options;
    }
}
