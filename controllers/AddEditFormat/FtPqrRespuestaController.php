<?php

namespace Saia\Pqr\Controllers\AddEditFormat;


use Exception;
use Saia\models\Contador;
use Saia\Pqr\Models\PqrForm;
use Saia\models\formatos\Formato;
use Saia\controllers\SessionController;
use Saia\models\formatos\CamposFormato;
use Saia\Pqr\Controllers\AddEditFormat\TAddEditFormat;

class FtPqrRespuestaController implements IAddEditFormat
{
    use TAddEditFormat;

    protected $PqrForm;

    public function __construct(PqrForm $PqrForm)
    {
        $this->PqrForm = $PqrForm;
    }

    public function createForm(): void
    {
        $data = $this->getFormatDefaultData();

        $id = Formato::newRecord($data);
        $this->PqrForm->setAttributes([
            'fk_formato_r' => $id
        ]);
        $this->PqrForm->update();

        $this->addEditFieldsToForm();
    }

    public function updateForm(): void
    {
        $data = $this->getFormatDefaultData(true);
        $FormatoR = new Formato($this->PqrForm->fk_formato_r);
        $FormatoR->setAttributes($data);

        $this->addEditFieldsToForm();
    }

    public function generateForm(): void
    {
        $this->FormatGenerator($this->PqrForm->fk_formato_r);
    }

    protected function getFormatDefaultData(bool $edit = false): array
    {
        if (!$Contador = Contador::findByAttributes(['nombre' => 'radicacion_salida'])) {
            throw new Exception("Contador Interno - Externo No encontrado", 1);
        }

        $name = 'pqr_respuesta';
        $data = [
            'nombre' => $name,
            'etiqueta' => 'RESPUESTA PQR',
            'cod_padre' => 0,
            'contador_idcontador' => $Contador->getPK(),
            'nombre_tabla' => "ft_{$name}",
            'ruta_mostrar' => "app/modules/back_pqr/formatos/{$name}/mostrar.php",
            'ruta_editar' => "app/modules/back_pqr/formatos/{$name}/editar.php",
            'ruta_adicionar' => "app/modules/back_pqr/formatos/{$name}/adicionar.php",
            'ruta_buscar' => "app/modules/back_pqr/formatos/{$name}/buscar.php",
            'encabezado' => 1,
            'cuerpo' => '{*showTemplate*}{*mostrar_estado_proceso*}',
            'pie_pagina' => 0,
            'margenes' => '25,25,25,25',
            'orientacion' => 0,
            'papel' => 'Letter',
            'exportar' => 'mpdf',
            'funcionario_idfuncionario' => SessionController::getValue('idfuncionario'),
            'detalle' => 0,
            'tipo_edicion' => 0,
            'item' => 0,
            'font_size' => 11,
            'banderas' => NULL,
            'mostrar_pdf' => 1,
            'orden' => NULL,
            'firma_digital' => 0,
            'fk_categoria_formato' => NULL,
            'funcion_predeterminada' => NULL,
            'paginar' => 0,
            'pertenece_nucleo' => 0,
            'descripcion_formato' => 'Formulario utilizado para responder las PQR',
            'version' => 1,
            'module' => 'pqr'
        ];

        if ($edit) {
            unset($data['funcionario_idfuncionario']);
        }

        return $data;
    }

    protected function getFormatFieldData(): array
    {
        return [
            'fk_response_template' => [
                'formato_idformato' => $this->PqrForm->fk_formato_r,
                'autoguardado' => 0,
                'fila_visible' => 1,
                'obligatoriedad' => 1,
                'orden' => 1,
                'nombre' => 'fk_response_template',
                'etiqueta' => 'Plantilla',
                'tipo_dato' => 'integer',
                'longitud' => 11,
                'etiqueta_html' => 'opciones_sql',
                'acciones' => 'a,e,p',
                'placeholder' => '',
                'listable' => 1,
                'opciones' => '{"tipo":"select","sql":"SELECT id,name as nombre FROM pqr_response_template WHERE active=1"}',
                'ayuda' => NULL,
                'longitud_vis' => NULL
            ],
            'fk_response_template_json' => [
                'formato_idformato' => $this->PqrForm->fk_formato_r,
                'autoguardado' => 0,
                'fila_visible' => 1,
                'obligatoriedad' => 0,
                'orden' => 1,
                'nombre' => 'fk_response_template_json',
                'etiqueta' => 'fk_response_template_json',
                'tipo_dato' => 'text',
                'longitud' => NULL,
                'etiqueta_html' => 'system_field',
                'acciones' => NULL,
                'placeholder' => NULL,
                'listable' => 0,
                'opciones' => NULL,
                'ayuda' => NULL,
                'longitud_vis' => NULL
            ],
            'email' => [
                'formato_idformato' => $this->PqrForm->fk_formato_r,
                'autoguardado' => 0,
                'fila_visible' => 1,
                'obligatoriedad' => 0,
                'orden' => 2,
                'nombre' => 'email',
                'etiqueta' => 'Responder a (Email):',
                'tipo_dato' => 'string',
                'longitud' => NULL,
                'etiqueta_html' => 'text',
                'acciones' => NULL,
                'placeholder' => 'Ingrese los correos',
                'listable' => 1,
                'opciones' => NULL,
                'ayuda' => 'Ingrese el o los correos, separados por coma a los cuales se le remitira la respuesta',
                'longitud_vis' => NULL
            ],
            'content' => [
                'formato_idformato' => $this->PqrForm->fk_formato_r,
                'autoguardado' => 0,
                'fila_visible' => 1,
                'obligatoriedad' => 1,
                'orden' => 3,
                'nombre' => 'content',
                'etiqueta' => 'Contenido',
                'tipo_dato' => 'string',
                'longitud' => NULL,
                'etiqueta_html' => 'textarea_cke',
                'acciones' => NULL,
                'placeholder' => NULL,
                'listable' => 1,
                'opciones' => '{"avanzado":true}',
                'ayuda' => NULL,
                'longitud_vis' => NULL
            ],
            'adjuntos' => [
                'formato_idformato' => $this->PqrForm->fk_formato_r,
                'autoguardado' => 0,
                'fila_visible' => 1,
                'obligatoriedad' => 0,
                'orden' => 4,
                'valor' => '.pdf|.doc|.docx|.jpg|.jpeg|.gif|.png|.bmp|.xls|.xlsx|.ppt@multiple',
                'nombre' => 'adjuntos',
                'etiqueta' => 'Anexos',
                'tipo_dato' => 'string',
                'longitud' => NULL,
                'etiqueta_html' => 'archivo',
                'acciones' => NULL,
                'placeholder' => NULL,
                'listable' => 1,
                'opciones' => '{"tipos":".pdf,.doc,.docx,.jpg,.jpeg,.gif,.png,.bmp,.xls,.xlsx,.ppt","longitud":"3","cantidad":"3"}',
                'ayuda' => 'Anexos que se enviaran en la respuesta',
                'longitud_vis' => NULL
            ]
        ];
    }
    protected function addEditFieldsToForm(): self
    {
        $data = $this->getFormatFieldData();
        foreach ($data as $key => $row) {
            if ($CamposFormato = CamposFormato::findByAttributes([
                'nombre' => $key,
                'formato_idformato' => $this->PqrForm->fk_formato_r
            ])) {
                $CamposFormato->setAttributes($row);
                $CamposFormato->update();
            } else {
                CamposFormato::newRecord($row);
            }
        }
        return $this;
    }
}
