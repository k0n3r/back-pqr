<?php

namespace App\Bundles\pqr\Services\controllers\AddEditFormat\fields;

use ReflectionClass;

trait TField
{
    public function getDefaultValues(): array
    {
        $PqrFormField = $this->PqrFormField;

        return [
            'formato_idformato' => $PqrFormField->PqrForm->fk_formato,
            'nombre' => $PqrFormField->name,
            'etiqueta' => $PqrFormField->label,
            'tipo_dato' => 'string',
            'longitud' => 255,
            'obligatoriedad' => $PqrFormField->required,
            'valor' => NULL,
            'acciones' => implode(',', $this->getActions()),
            'ayuda' => NULL,
            'predeterminado' => NULL,
            'banderas' => NULL,
            'etiqueta_html' => (new ReflectionClass($this))->getShortName(),
            'orden' => $PqrFormField->orden,
            'adicionales' => NULL,
            'fila_visible' => 1,
            'placeholder' => NULL,
            'longitud_vis' => NULL,
            'opciones' => NULL,
            'estilo' => NULL,
            'listable' => 1
        ];
    }
}
