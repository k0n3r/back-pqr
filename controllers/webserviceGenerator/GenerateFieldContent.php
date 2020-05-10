<?php

namespace Saia\Pqr\controllers\webserviceGenerator;

use Saia\Pqr\controllers\webserviceGenerator\fieldGenerator\FieldFormatGeneratorInterface;

class GenerateFieldContent
{
    /**
     * Una clase que implementa de FieldFormatGeneratorInterface para
     * generar el contenido html y js del webservice
     *
     * @var FieldFormatGeneratorInterface
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    protected $TypeField;

    public function __construct(FieldFormatGeneratorInterface $TypeField)
    {
        $this->TypeField = $TypeField;
    }

    /**
     * pivote que obtiene el contenido del campo que sera incluido en el
     * webservice
     *
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function getContent(): string
    {
        return $this->TypeField->getFieldContent();
    }

    /**
     * pivote que obtiene los scripts adicionales que deben ser incluidos 
     * en el webservice
     *
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function getAditionalFiles(): array
    {
        return $this->TypeField->getAditionalFiles();
    }

    /**
     * pivote para obtener el JS adicional que sera incluido en el webservice
     *
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function getJsAditionalContent(): string
    {
        return $this->TypeField->getJsAditionalContent();
    }
}
