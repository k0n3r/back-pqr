<?php

namespace Saia\Pqr\Controllers;

use Saia\Pqr\Models\PqrForm;
use Saia\Pqr\Controllers\WebserviceGenerator\WebserviceGenerator;

class WebservicePqr extends WebserviceGenerator
{
    /**
     * Instancia de PqrForm
     *
     * @var PqrForm
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public $PqrForm;

    public function __construct(PqrForm $PqrForm)
    {
        $this->PqrForm = $PqrForm;
    }

    /**
     * Obtiene el ID del formato
     *
     * @return int
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    protected function getFormatId(): int
    {
        return (int) $this->PqrForm->fk_formato;
    }

    /**
     * Obtiene los campos del formulario que estaran en el adicionar del webservice
     *
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    protected function getFormatFields(): array
    {
        $data = [];

        foreach ($this->PqrForm->getPqrFormFieldsActive() as $PqrFormField) {
            $data[] = $PqrFormField->CamposFormato;
        }

        return $data;
    }
    /**
     * Obtiene el nombre del formulario
     *
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    protected function getNameForm(): string
    {
        return $this->PqrForm->label;
    }

    /**
     * Obtiene el contenido html de los campos que estaran en el adicionar
     * del webservice
     *
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    protected function getContent(): string
    {
        $code = parent::getContentDefault();

        return $code;
    }

    /**
     * Crea el contenido js que sera cargado en el adicionar del webservice
     *
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    protected function createJsContent(): string
    {
        return parent::jsContentDefault('app/modules/back_pqr/app/webservice.php');
    }
}
