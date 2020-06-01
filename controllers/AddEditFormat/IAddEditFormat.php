<?php

namespace Saia\Pqr\controllers\addEditFormat;


interface IAddEditFormat
{
    /**
     * Actualiza el formulario
     *
     * @return boolean
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function updateChange(): bool;

    /**
     * Genera los archivos mostrar, editar etc
     *
     * @return boolean
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function generateForm(): bool;
}
