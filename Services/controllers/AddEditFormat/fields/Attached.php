<?php

namespace App\Bundles\pqr\Services\controllers\AddEditFormat\fields;

class Attached extends Field implements IField
{
    use TField;

    /**
     * @inheritDoc
     */
    public function getValues(): array
    {
        $setting = $this->getPqrFormField()->getSetting();

        $data = array_merge($this->getDefaultValues(), [
            'valor' => $setting->typeFiles,
            'opciones' => '{"tipos":"' . $setting->typeFiles . '","longitud":"3","cantidad":"' . $setting->numberFiles . '","ruta_consulta":"app/anexos/consultar_anexos_campo.php"}'
        ]);

        if (!$this->getPqrFormField()->active) {
            $data['etiqueta_html'] = 'Hidden';
            $data['opciones'] = '{"type":"hidden"}';
        }
        return $data;
    }
}
