<?php

namespace Saia\Pqr\webserviceGenerator;

interface IWsHtml
{
    /**
     * Se debe implementar y retornar el contenido HTML que tendra el formulario pricipal del ws
     *
     * @param array $filesToInclude : array que tiene las URL de los archivos js/cs etc que se deberan
     *                                incluir en el contenido HTML
     * @param string|null $urlSearch : Url del archivo de busqueda del formulario, si llega NULL es por que 
     *                                 este formulario NO utilizara busqueda
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function getHtmlContentForm(array $filesToInclude, ?string $urlSearch): string;

    /**
     * Se debe implementar y retornar el contenido JS que se usara en el ws para el formulario del ws
     *
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function getJsContentForm(): string;

    /**
     * Se debe implementar y retornar el contenido HTML que tendra el buscador pricipal del ws
     *
     * @param array $filesToInclude : array que tiene las URL de los archivos js/cs etc que se deberan
     *                                incluir en el contenido HTML
     * @param string|null $urlSearch : Url del archivo de busqueda del formulario, si llega NULL es por que 
     *                                 este formulario NO utilizara busqueda
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function getHtmlContentSearchForm(array $filesToInclude, string $urlForm): string;

    /**
     * Se debe implementar y retornar el contenido JS que se usara en el buscador principal ws
     *
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function getJsContentSearchForm(): string;
}
