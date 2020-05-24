<?php

namespace Saia\Pqr\webserviceGenerator;


interface IWsFields
{

    /**
     * Urls (desde la raiz) del los archivos js/css etc que se utilizan en el componente
     *
     * @return array 
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function aditionalFiles(): array;

    /**
     * Contenido html del componente
     *
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function htmlContent(): string;

    /**
     * Contenido JS que se utiliza para el buen funcionamiento del componente
     *
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function jsContent(): ?string;
}
