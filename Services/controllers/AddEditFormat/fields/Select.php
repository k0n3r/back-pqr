<?php

namespace App\Bundles\pqr\Services\controllers\AddEditFormat\fields;

class Select extends Field implements IField
{

    use TField;

    /**
     * @inheritDoc
     */
    public function getValues(): array
    {
        $data = array_merge($this->getDefaultValues(), [
            'tipo_dato' => 'integer',
            'longitud' => 11,
        ]);

        if (!$this->getPqrFormField()->active && $this->getPqrFormField()->name != 'sys_subtipo') {
            $data['etiqueta_html'] = 'Hidden';
            $data['opciones'] = '{"type":"hidden"}';
        }

        return $data;
    }
}
