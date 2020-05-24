<?php

namespace Saia\Pqr\webserviceGenerator\fieldGenerator;

use Saia\models\formatos\CamposFormato;

class Field
{
    /**
     * Instancia de CamposFormato
     *
     * @var CamposFormato
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    protected CamposFormato $CamposFormato;

    public function __construct(CamposFormato $CamposFormato)
    {
        $this->CamposFormato = $CamposFormato;
    }

    /**
     * Devuelve required "css" para los campos obligatorios
     *
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    protected function getRequiredClass(): string
    {
        return $this->CamposFormato->obligatoriedad ? "required" : '';
    }

    /**
     * Devuelve el value por defecto del campo
     *
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    protected function getDefaultValue(): ?string
    {
        return $this->CamposFormato->predeterminado;
    }

    /**
     * Devuelve la etiqueta del campo
     *
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    protected function getLabel(): string
    {
        return strtoupper($this->CamposFormato->etiqueta);
    }
}
