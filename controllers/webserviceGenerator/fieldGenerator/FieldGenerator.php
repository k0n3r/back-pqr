<?php

namespace Saia\Pqr\Controllers\WebserviceGenerator\FieldGenerator;

use Exception;
use Saia\models\formatos\CampoOpciones;
use Saia\models\formatos\CamposFormato;

class FieldGenerator
{

    protected $CamposFormato;

    public function __construct(CamposFormato $CamposFormato)
    {
        $this->CamposFormato = $CamposFormato;
    }

    protected function getRequiredClass()
    {
        return $this->CamposFormato->obligatoriedad ? "required" : '';
    }

    protected function getDafaultValue()
    {
        return $this->CamposFormato->predeterminado;
    }

    protected function getLabel()
    {
        return strtoupper($this->CamposFormato->etiqueta);
    }

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
}
