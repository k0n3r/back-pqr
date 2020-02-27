<?php

namespace Saia\Pqr\formatos\pqr;

use Exception;
use Saia\controllers\UtilitiesController;
use Saia\Pqr\Models\PqrForm;
use Saia\Pqr\Models\PqrBackup;
use Saia\models\formatos\CampoSeleccionados;


class FtPqr extends FtPqrProperties
{
    public function __construct($id = null)
    {
        parent::__construct($id);
    }

    protected function defineMoreAttributes(): array
    {
        return [
            'relations' => [
                'PqrBackup' => [
                    'model' => PqrBackup::class,
                    'attribute' => 'fk_documento',
                    'primary' => 'documento_iddocumento',
                    'relation' => self::BELONGS_TO_ONE
                ]
            ]
        ];
    }
    /**
     * Obtiene las valores del modelo para guardarlos en el backup
     *
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    protected function getDataRow(): array
    {
        if (!$PqrForm = PqrForm::getPqrFormActive()) {
            throw new Exception("No se encuentra el formulario activo", 1);
        }
        $data = [];

        $Fields = $PqrForm->getPqrFormFieldsActive();
        foreach ($Fields as  $PqrFormField) {
            $PqrHtmlField = $PqrFormField->PqrHtmlField;
            if (in_array($PqrHtmlField->type, [
                'radio',
                'checkbox',
                'select'
            ])) {
                $data[$PqrFormField->label] = CampoSeleccionados::findColumn('valor', [
                    'fk_campos_formato' => $PqrFormField->fk_campos_formato,
                    'fk_documento' => $this->documento_iddocumento
                ]);
            } else {
                $fieldName = $PqrFormField->name;
                $data[$PqrFormField->label] = $this->$fieldName;
            }
        }
        return $data;
    }

    /**
     * Funcion ejecutada posterior al adicionar una PQR
     *
     * @return void
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function afterAdd()
    {
        if (!PqrBackup::newRecord([
            'fk_documento' => $this->documento_iddocumento,
            'fk_pqr' => $this->getPK(),
            'data' => json_encode($this->getDataRow())
        ])) {
            throw new Exception("No fue posible registrar el backup", 1);
        }
    }
    /**
     * Funcion ejecutada despues de editar un documento
     *
     * @return void
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function afterEdit()
    {
        if ($PqrBAckup = PqrBackup::findByAttributes([
            'fk_documento' => $this->documento_iddocumento
        ])) {
            $PqrBAckup->setAttributes([
                'data' => json_encode($this->getDataRow())
            ]);
            if (!$this->update()) {
                throw new Exception("No fue posible actualizar el backup", 1);
            }
        } else {
            $this->afterAdd();
        }
    }

    /**
     * Carga todo el mostrar del formulario
     *
     * @return void
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function showContent(): string
    {
        $data = json_decode($this->PqrBackup->data);
        $Qr = UtilitiesController::mostrar_qr($this);
        $code = "<table class='table table-bordered' style='width:100%'>
        <tr>
            <td colspan='2' align='right'>{$Qr}</td>
        </tr>";
        foreach ($data as $key => $value) {
            $val = (is_array($value)) ? implode(',', $value) : $value;
            $code .= "<tr>
                <td class='text-uppercase font-weight-bold' style='width:35%'>{$key} :</td>
                <td style='width:65%'>{$val}</td>
            <tr>";
        }
        $code .= '</table>';

        return $code;
    }
}
