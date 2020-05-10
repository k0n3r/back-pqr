<?php

namespace Saia\Pqr\controllers\webserviceGenerator\fieldGenerator;


interface FieldFormatGeneratorInterface
{
    /**
     * Contenido html del componente
     *
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function getFieldContent(): string;

    /**
     * Urls del los archivos js/css que se utilizan en el componente
     *
     * @return array 
     * [
     *      [
     *          'origin' => 'url to file',
     *          'fieldName' => 'new name to file'
     *          'type' => WebserviceGenerator::TYPE_CSS
     *      ]
     *  ]
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function getAditionalFiles(): array;

    /**
     * Contenido Js adicional que se utiliza para cargar el componente
     *
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function getJsAditionalContent(): string;
}
