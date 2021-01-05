<?php

namespace App\Bundles\pqr\Services\controllers\AddEditFormat\fields;

interface IField
{
    /**
     * Obtiene vector con valores para insertar en campos formato
     *
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function getValues(): array;
}
