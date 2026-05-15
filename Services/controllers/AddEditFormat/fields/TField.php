<?php

namespace App\Bundles\pqr\Services\controllers\AddEditFormat\fields;

use ReflectionClass;

trait TField
{
    /**
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2021-05-28
     */
    public function getDefaultValues(): array
    {
        $PqrFormField = $this->getPqrFormField();

        return [
            'formato_idformato' => $PqrFormField->getPqrForm()->getFkFormato(),
            'nombre'            => $PqrFormField->getName(),
            'etiqueta'          => $PqrFormField->getLabel(),
            'tipo_dato'         => 'string',
            'longitud'          => 255,
            'obligatoriedad'    => $PqrFormField->isRequired(),
            'valor'             => null,
            'acciones'          => implode(',', $this->getActions()),
            'ayuda'             => null,
            'predeterminado'    => null,
            'banderas'          => null,
            'etiqueta_html'     => (new ReflectionClass($this))->getShortName(),
            'orden'             => $PqrFormField->getOrden(),
            'adicionales'       => null,
            'fila_visible'      => 1,
            'placeholder'       => null,
            'longitud_vis'      => null,
            'opciones'          => null,
            'estilo'            => null,
            'listable'          => 1,
        ];
    }
}
