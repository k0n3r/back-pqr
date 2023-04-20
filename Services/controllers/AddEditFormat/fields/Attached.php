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
            'opciones' => '{"tipos":"' . $setting->typeFiles . '","longitud":"' . $this->getSize() . '","cantidad":"' . $setting->numberFiles . '","ruta_consulta":"api/documentFile/info"}'
        ]);

        if (!$this->getPqrFormField()->active) {
            $data['etiqueta_html'] = 'Hidden';
            $data['opciones'] = '{"type":"hidden"}';
        }
        return $data;
    }

    /**
     * Obtiene el tamaño permitido
     *
     * @return int
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2021-06-09
     */
    private function getSize(): int
    {
        $size = 3;
        $CampoFormato = $this->getPqrFormField()->getCamposFormato();
        if ($CampoFormato->getPK()) {
            $size = ((int)$CampoFormato->getOptions()->longitud) ?: 3;
        }

        return $size;
    }
}
