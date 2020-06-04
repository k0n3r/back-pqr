<?php

namespace Saia\Pqr\formatos\pqr;

use Exception;
use Saia\Pqr\models\PqrForm;
use Saia\Pqr\models\PqrBackup;
use Saia\Pqr\models\PqrFormField;
use Saia\Pqr\Helpers\UtilitiesPqr;
use Saia\controllers\anexos\FileJson;
use Saia\controllers\SendMailController;
use Saia\models\formatos\CampoSeleccionados;
use Saia\controllers\functions\CoreFunctions;
use Saia\Pqr\formatos\pqr_respuesta\FtPqrRespuesta;
use Saia\Pqr\controllers\services\PqrFormFieldService;

class FtPqr extends FtPqrProperties
{
    const ESTADO_PENDIENTE = 'PENDIENTE';
    const ESTADO_PROCESO = 'PROCESO';
    const ESTADO_TERMINADO = 'TERMINADO';

    protected function defineMoreAttributes(): array
    {
        return [
            'relations' => [
                'PqrBackup' => [
                    'model' => PqrBackup::class,
                    'attribute' => 'fk_documento',
                    'primary' => 'documento_iddocumento',
                    'relation' => self::BELONGS_TO_ONE
                ],
                'PqrRespuesta' => [
                    'model' => FtPqrRespuesta::class,
                    'attribute' => 'ft_pqr',
                    'primary' => 'idft_pqr',
                    'relation' => self::BELONGS_TO_MANY
                ],
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
    private function getDataRow(): array
    {
        if (!$PqrForm = PqrForm::getPqrFormActive()) {
            throw new Exception("No se encuentra el formulario activo", 1);
        }
        $data = [];

        $Fields = $PqrForm->PqrFormFields;
        foreach ($Fields as  $PqrFormField) {
            $PqrHtmlField = $PqrFormField->PqrHtmlField;
            if (in_array($PqrHtmlField->type_saia, [
                'Radio',
                'Checkbox',
                'Select'
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

    private function validSysEmail()
    {
        if ($this->sys_email) {
            if (!$this->isEmailValid($this->sys_email)) {
                throw new Exception("Esta dirección de correo ({$this->sys_email}) no es válida.", 200);
            }
        }
        return true;
    }

    private function isEmailValid(string $email): bool
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        return true;
    }

    /**
     * Funcion ejecutada posterior al adicionar una PQR
     *
     * @return boolean
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function afterAdd(): bool
    {
        return $this->validSysEmail();
    }

    /**
     * Funcion ejecutada despues de editar un documento
     *
     * @return boolean
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function afterEdit(): bool
    {
        return $this->validSysEmail();
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
        $Qr = CoreFunctions::mostrar_qr($this);
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

    /**
     *@inheritDoc
     */
    public function afterRad(): bool
    {
        $this->Documento->getPdfJson(true);

        return $this->createBackup() &&
            $this->notifyEmail();
    }

    private function createBackup(): bool
    {
        if (!PqrBackup::newRecord([
            'fk_documento' => $this->documento_iddocumento,
            'fk_pqr' => $this->getPK(),
            'data' => json_encode($this->getDataRow())
        ])) {
            throw new Exception("No fue posible registrar el backup", 1);
        }
        return true;
    }

    private function notifyEmail(): bool
    {
        if (!$this->sys_email) {
            return true;
        }

        $message = "Cordial Saludo,<br/><br/>Su solicitud ha sido generada con el número de radicado {$this->Documento->numero}, adjunto encontrará una copia de la PQR diligenciada el día de hoy.<br/><br/>
        El seguimiento lo puede realizar escaneando el código QR";

        $SendMailController = new SendMailController(
            "Solicitud de PQR # {$this->Documento->numero}",
            $message
        );

        $SendMailController->setDestinations(
            SendMailController::DESTINATION_TYPE_EMAIL,
            [$this->sys_email]
        );

        $File = new FileJson($this->Documento->getPdfJson());
        $SendMailController->setAttachments([$File]);

        $send = $SendMailController->send();
        if ($send !== true) {
            $log = [
                'error' => $send,
                'message' => "No fue posible notificar la PQR # {$this->Documento->numero}"
            ];
            UtilitiesPqr::notifyAdministrator(
                "No fue posible notificar la PQR # {$this->Documento->numero}",
                $log
            );
        }

        return true;
    }

    public function getPqrAnswers(): array
    {
        $data = [];
        foreach ($this->PqrRespuesta as $FtPqrRespuesta) {
            if (!$FtPqrRespuesta->Documento->isActive()) {
                $data[] = $FtPqrRespuesta;
            }
        }

        return $data;
    }

    public function autocompleteD(int $idCamposFormato): string
    {
        $PqrFormField = PqrFormField::findByAttributes([
            'fk_campos_formato' => $idCamposFormato
        ]);
        return $this->generateField($PqrFormField);
    }

    public function autocompleteM(int $idCamposFormato): string
    {
        $PqrFormField = PqrFormField::findByAttributes([
            'fk_campos_formato' => $idCamposFormato
        ]);

        return $this->generateField($PqrFormField);
    }

    private function generateField(PqrFormField $PqrFormField): string
    {
        $name = $PqrFormField->name;
        $required = $PqrFormField->required ? 'required' : '';

        $options = '';
        if ($this->$name) {
            $list = (new PqrFormFieldService($PqrFormField))->getListField(['id' => $this->$name]);
            if ($list) {
                $options .= "<option value='{$list[0]['id']}' selected='selected'>{$list[0]['text']}</option>";
            }
        }

        $code = <<<HTML
    <div class='form-group form-group-default form-group-default-select2 {$required}' id='group_{$name}'>
        <label>{$PqrFormField->label}</label>
        <div class='form-group'>
            <select class='full-width pqrAutocomplete {$required}' name='{$name}' id='{$name}'>
                {$options}
            </select>
        </div>
    </div>
HTML;
        return $code;
    }
}
