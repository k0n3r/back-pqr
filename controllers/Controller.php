<?php

namespace Saia\Pqr\controllers;

class Controller
{
    /**
     * Variable que contiene todo el request que llega de las peticiones
     *
     * @var array|null
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public $request;

    // public static function __callStatic($name, $arguments): ?object
    // {
    //     $caller = get_called_class();
    //     $Controller = new $caller($arguments);
    //     if (method_exists($Controller, $name)) {
    //         return $Controller->$name();
    //     }
    //     return null;
    // }
}
