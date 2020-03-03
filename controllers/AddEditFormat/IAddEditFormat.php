<?php

namespace Saia\Pqr\Controllers\AddEditFormat;


interface IAddEditFormat
{
    /**
     * Crea el formulario
     *
     * @return void
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function createForm(): void;

    /**
     * Actualiza el formulario
     *
     * @return void
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function updateForm(): void;

    /**
     * Genera los archivos mostrar, editar etc
     *
     * @return void
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function generateForm(): void;
}
