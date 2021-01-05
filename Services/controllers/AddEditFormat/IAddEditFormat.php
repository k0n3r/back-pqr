<?php

namespace App\Bundles\pqr\Services\controllers\AddEditFormat;


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
}
