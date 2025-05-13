<?php

namespace App\Bundles\pqr\Services\controllers\AddEditFormat\fields;

use App\Bundles\pqr\Services\models\PqrFormField;

use Saia\models\formatos\CamposFormato;

abstract class Field
{
    /**
     * Campos que seran utilizados como descripcion/detalle en el modulo
     */
    public const array FIELDS_DESCRIPTION = [
        'sys_tipo',
        'sys_email',
        'sys_estado',
    ];

    private PqrFormField $PqrFormField;

    public function __construct(PqrFormField $PqrFormField)
    {
        $this->PqrFormField = $PqrFormField;
    }

    public function getPqrFormField(): PqrFormField
    {
        return $this->PqrFormField;
    }

    protected function getActions(): array
    {
        $actions = [
            CamposFormato::ACTION_ADD,
            CamposFormato::ACTION_EDIT,
        ];

        if ($this->PqrFormField->required) {
            if (in_array($this->PqrFormField->name, self::FIELDS_DESCRIPTION)) {
                $actions[] = CamposFormato::ACTION_DESCRIPTION;
            }
        }

        return $actions;
    }
}
