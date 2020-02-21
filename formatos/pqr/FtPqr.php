<?php

namespace Saia\Pqr\formatos\pqr;

class FtPqr extends FtPqrProperties
{
    public function __construct($id = null)
    {
        parent::__construct($id);
    }

    /**
     * Carga todo el mostrar del formulario
     *
     * @return void
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function showContent(): string
    {
        $code = 'ESTE ES EL MOSTRAR';

        return $code;
    }
}
