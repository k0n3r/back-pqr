<?php

namespace App\Bundles\pqr\Services\controllers\AddEditFormat;

use Saia\models\formatos\Formato;

interface IAddEditFormat
{
    /**
     * Actualiza el formulario
     *
     * @return bool
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function updateChange(): bool;

    /**
     * Obtiene el Formato
     *
     * @return Formato
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2021
     */
    public function getFormat(): Formato;
}
