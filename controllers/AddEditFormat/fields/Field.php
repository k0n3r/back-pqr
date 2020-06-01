<?php

namespace Saia\Pqr\controllers\addEditFormat\fields;

use Saia\Pqr\models\PqrFormField;
use Saia\models\formatos\CamposFormato;

abstract class Field
{
    /**
     * Campos que seran utilizados como descripcion/detalle en el modulo 
     */
    const FIELDS_DESCRIPTION = [
        'sys_tipo',
        'sys_email',
        'sys_estado'
    ];

    protected PqrFormField $PqrFormField;

    public function __construct(PqrFormField $PqrFormField)
    {
        $this->PqrFormField = $PqrFormField;
    }

    protected function getActions(): array
    {
        $actions = [
            CamposFormato::ACTION_ADD,
            CamposFormato::ACTION_EDIT
        ];

        if ($this->PqrFormField->required) {
            if (in_array($this->PqrFormField->name, self::FIELDS_DESCRIPTION)) {
                $actions[] = CamposFormato::ACTION_DESCRIPTION;
            }
        }
        return $actions;
    }
}
