<?php

namespace Saia\Pqr\controllers;

abstract class Controller
{
    /**
     * Variable que contiene todo el request que llega de las peticiones
     *
     * @var array|null
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public $request;

    public function __construct(array $request = null)
    {
        $this->request = $request;
    }
}
