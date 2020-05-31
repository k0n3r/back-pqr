<?php

namespace Saia\Pqr\controllers\services;


class PqrFormService extends Service
{

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
        if ($records = $this->Model->PqrFormFields) {
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
        return $this->Model->getDataAttributes();
    }
}
