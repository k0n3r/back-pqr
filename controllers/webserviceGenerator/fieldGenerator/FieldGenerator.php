<?php

namespace Saia\Pqr\Controllers\WebserviceGenerator\FieldGenerator;

use Exception;
use Saia\models\formatos\CampoOpciones;
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
     * Devuelve las opciones de los campos
     * select, checkbox y radios
     *
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     * 
     * @throws Exception
     */
    protected function getMultipleOptions(): array
    {
        if (in_array($this->CamposFormato->etiqueta_html, [
            'radio',
            'select',
            'checkbox'
        ])) {
            $this->campoOpciones = CampoOpciones::findAllByAttributes([
                'fk_campos_formato' => $this->CamposFormato->getPK(),
                'estado' => 1
            ]);

            return $this->campoOpciones;
        } else {
            throw new Exception("El componente debe ser de tipo radio,checkbox o select", 1);
        }
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
