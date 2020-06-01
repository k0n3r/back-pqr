<?php

namespace Saia\Pqr\controllers\addEditFormat\fields;

class Attached extends Field implements IField
{
    use TField;

    /**
     * @inheritDoc
     */
    public function getValues(): array
    {
        $setting = $this->PqrFormField->getSetting();

        return array_merge($this->getDefaultValues(), [
            'valor' => $setting->typeFiles,
            'opciones' => '{"tipos":"' . $setting->typeFiles . '","longitud":"3","cantidad":"' . $$setting->numberFiles . '","ruta_consulta":"app/anexos/consultar_anexos_campo.php"}'
        ]);
    }
}
