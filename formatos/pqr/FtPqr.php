<?php

namespace Saia\Pqr\formatos\pqr;

use Exception;
use Saia\Pqr\Models\PqrForm;
use Saia\Pqr\Models\PqrBackup;
use Saia\Pqr\Helpers\UtilitiesPqr;
use Saia\controllers\SendMailController;
use Saia\models\formatos\CampoSeleccionados;
use Saia\controllers\functions\CoreFunctions;
use Saia\controllers\pdf\DocumentPdfGenerator;
use Saia\Pqr\formatos\pqr_respuesta\FtPqrRespuesta;

class FtPqr extends FtPqrProperties
{
    const ESTADO_PENDIENTE = 'PENDIENTE';
    const ESTADO_PROCESO = 'PROCESO';
    const ESTADO_TERMINADO = 'TERMINADO';

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
        if (!filter_var($this->sys_email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Esta dirección de correo ({$this->sys_email}) no es válida.", 200);
        }

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

    public function afterRad(): void
    {
        $this->notifyEmail();
    }

    public function notifyEmail()
    {
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

        if (!$this->Documento->pdf) {
            $DocumentPdfGenerator = new DocumentPdfGenerator($this->Documento);
            $route = $DocumentPdfGenerator->refreshFile();

            if (!$route) {
                $log = [
                    'error' => "MpdfController NO genero el PDF, iddoc: {$this->Documento->getPK()}",
                    'message' => "No fue posible generar el PDF para el formato PQR"
                ];
                UtilitiesPqr::notifyAdministrator(
                    "No fue posible generar el PDF para la PQR # {$this->Documento->numero}",
                    $log
                );
            } else {
                $SendMailController->setAttachments(
                    $SendMailController::ATTACHMENT_TYPE_JSON,
                    [$this->Documento->pdf]
                );
            }
        }

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
}
