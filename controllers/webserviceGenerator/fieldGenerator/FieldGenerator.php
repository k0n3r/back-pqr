<?php

namespace Saia\Pqr\controllers\webserviceGenerator\fieldGenerator;

use Saia\models\formatos\CamposFormato;

class FieldGenerator
{
    /**
     * Instancia de CamposFormato
     *
     * @var CamposFormato
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    protected $CamposFormato;

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
     * @return void
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    protected function getLabel()
    {
        return strtoupper($this->CamposFormato->etiqueta);
    }

    /**
     * Adiciona tabulaciones al contenido
     *
     * @param string $code
     * @return void
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    protected function addTab(string $code): string
    {
        return str_replace("{n}", "\n", $code);
    }
}
