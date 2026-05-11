<?php

namespace App\Bundles\pqr\Services\controllers\AddEditFormat\fields;

use App\Bundles\pqr\Entity\PqrFormField as PqrFormFieldEntity;
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

    private PqrFormFieldEntity $PqrFormField;

    public function __construct(PqrFormFieldEntity $PqrFormField)
    {
        $this->PqrFormField = $PqrFormField;
    }

    public function getPqrFormField(): PqrFormFieldEntity
    {
        return $this->PqrFormField;
    }

    protected function getActions(): array
    {
        $actions = [
            CamposFormato::ACTION_ADD,
            CamposFormato::ACTION_EDIT,
        ];

        $isDescription = false;
        if ($this->PqrFormField->isRequired()) {
            if (in_array($this->PqrFormField->getName(), self::FIELDS_DESCRIPTION)) {
                $actions[] = CamposFormato::ACTION_DESCRIPTION;
                $isDescription = true;
            }
        }

        if (!$isDescription) {
            if ($this->PqrFormField->getPqrForm()->getDescriptionField() === $this->PqrFormField->getId()) {
                $actions[] = CamposFormato::ACTION_DESCRIPTION;
            }
        }

        return $actions;
    }
}
