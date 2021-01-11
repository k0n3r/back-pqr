<?php

namespace App\Bundles\pqr\Services\controllers\AddEditFormat;

use App\Bundles\pqr\Services\models\PqrForm;
use App\Bundles\pqr\formatos\pqr\FtPqr;
use Saia\models\formatos\Formato;
use App\Bundles\pqr\Services\models\PqrFormField;
use Saia\controllers\SessionController;
use Saia\models\formatos\CampoOpciones;
use Saia\models\formatos\CamposFormato;

class AddEditFtPqr implements IAddEditFormat
{
    /**
     * Intancia de PqrForm
     */
    private PqrForm $PqrForm;

    /**
     *
     * @param PqrForm $PqrForm
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function __construct(PqrForm $PqrForm)
    {
        $this->PqrForm = $PqrForm;
    }

    /**
     * @inheritDoc
     */
    public function updateChange(): bool
    {
        $this->PqrForm->fk_formato ?
            $this->updateForm() : $this->createForm();

        return true;
    }

    /**
     * @inheritDoc
     */
    public function getFormat(): Formato
    {
        return $this->PqrForm->Formato;
    }

    /**
     * Genera la creacion del formato
     *
     * @return void
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    private function createForm(): void
    {
        $this->createRecordInFormat()
            ->addEditRecordsInFormatFields()
            ->addOtherFields();
    }

    /**
     * Actualiza la generacion del formato
     *
     * @return void
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    private function updateForm(): void
    {
        $this->updateRecordInFormat()
            ->addEditRecordsInFormatFields()
            ->addOtherFields();
    }


    /**
     * Obtiene los datos por defecto para la creacion del registro en Formato
     *
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     * 
     */
    private function getFormatDefaultData(bool $edit = false): array
    {
        $name = $this->PqrForm->name;
        $data = [
            'nombre' => $name,
            'etiqueta' => $this->PqrForm->label,
            'cod_padre' => 0,
            'contador_idcontador' => $this->PqrForm->fk_contador,
            'nombre_tabla' => "ft_{$name}",
            'ruta_mostrar' => "views/modules/pqr/formatos/{$name}/mostrar.php",
            'ruta_editar' => "views/modules/pqr/formatos/{$name}/editar.php",
            'ruta_adicionar' => "views/modules/pqr/formatos/{$name}/adicionar.php",
            'ruta_buscar' => "views/modules/pqr/formatos/{$name}/buscar.php",
            'encabezado' => 1,
            'cuerpo' => '<p>{*showContent*}</p><p>{*mostrar_estado_proceso*}</p>',
            'pie_pagina' => 0,
            'margenes' => '25,25,30,25',
            'orientacion' => 0,
            'papel' => 'Letter',
            'funcionario_idfuncionario' => SessionController::getValue('idfuncionario'),
            'detalle' => 0,
            'tipo_edicion' => 0,
            'item' => 0,
            'font_size' => 11,
            'banderas' => 'e', //Aprobacion automatica
            'mostrar_pdf' => 1,
            'orden' => 0,
            'fk_categoria_formato' => '3',
            'paginar' => 0,
            'pertenece_nucleo' => 0,
            'descripcion_formato' => 'Modulo de PQR',
            'version' => 1,
            'module' => 'pqr',
            'class_name' => 'Saia\Pqr\controllers\TaskEvents',
            'publicar' => 1
        ];

        if ($edit) {
            unset($data['contador_idcontador']);
            unset($data['encabezado']);
            unset($data['pie_pagina']);
            unset($data['margenes']);
            unset($data['orientacion']);
            unset($data['papel']);
            unset($data['funcionario_idfuncionario']);
            unset($data['fk_categoria_formato']);
        }

        return $data;
    }

    /**
     * Crea el registro en Formato
     *
     * @return self
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    private function createRecordInFormat(): self
    {
        $id = Formato::newRecord($this->getFormatDefaultData());

        $this->PqrForm->setAttributes([
            'fk_formato' => $id
        ]);
        $this->PqrForm->update();

        if (!$Respuesta = Formato::findByAttributes([
            'nombre' => 'pqr_respuesta'
        ])) {
            throw new \Exception("No se encontro el formato RESPUESTA PQR", 1);
        }

        $Respuesta->setAttributes([
            'cod_padre' => $id
        ]);
        $Respuesta->update();

        return $this;
    }

    /**
     * Actualiza el registro en Formato
     *
     * @return self
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    private function updateRecordInFormat(): self
    {
        $Formato = new Formato($this->PqrForm->fk_formato);
        $Formato->setAttributes($this->getFormatDefaultData(true));
        $Formato->update();

        return $this;
    }

    /**
     * Adiciona o actualiza los registros para la creacion de los campos del formulario
     *
     * @return self
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    private function addEditRecordsInFormatFields(): self
    {
        $allowOptions = [
            'Select',
            'Radio',
            'Checkbox'
        ];
        $fields = $this->PqrForm->PqrFormFields;
        foreach ($fields as $PqrFormField) {
            if (!$PqrFormField->fk_campos_formato) {
                $this->createRecordInFormatFields($PqrFormField);
            } else {
                $this->updateRecordInFormatFields($PqrFormField);
            }

            if (
                in_array($PqrFormField->PqrHtmlField->type_saia, $allowOptions)
                && $PqrFormField->getSetting()->options
            ) {
                $this->addEditformatOptions($PqrFormField);
            }
        }
        return $this;
    }

    /**
     * Crea o edita las opciones de los campos tipo select, radio o checkbox
     *
     * @param PqrFormField $PqrFormField
     * @return void
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public static function addEditformatOptions(PqrFormField $PqrFormField): void
    {
        $CampoFormato = $PqrFormField->CamposFormato;
        $llave = 0;
        foreach ($CampoFormato->CampoOpciones as $CampoOpciones) {

            if ((int) $CampoOpciones->llave > $llave) {
                $llave = (int) $CampoOpciones->llave;
            }
            if ((int) $CampoOpciones->estado) {
                $CampoOpciones->setAttributes([
                    'estado' => 0
                ]);
                $CampoOpciones->update();
            }
        }

        $data = $values = [];

        foreach ($PqrFormField->getSetting()->options as $option) {

            if ($CampoOpciones = CampoOpciones::findByAttributes([
                'valor' => $option->text,
                'fk_campos_formato' => $CampoFormato->getPk()
            ])) {
                $CampoOpciones->setAttributes([
                    'estado' => 1
                ]);
                $CampoOpciones->update();
                $id = $CampoOpciones->llave;
                $idCampoOpcion = $CampoOpciones->getPK();
            } else {
                $id = $llave + 1;
                $llave = $id;
                $idCampoOpcion = CampoOpciones::newRecord([
                    'llave' => $id,
                    'valor' => $option->text,
                    'fk_campos_formato' => $CampoFormato->getPK(),
                    'estado' => 1
                ]);
            }
            if ($PqrFormField->name == 'sys_tipo') {
                $data[] = [
                    'idcampo_opciones' => $idCampoOpcion,
                    'llave' => $id,
                    'item' => $option->text,
                    'dias' => $option->dias
                ];
            } else {
                $data[] = [
                    'llave' => $id,
                    'item' => $option->text
                ];
            }
            $values[] = "{$id},{$option->text}";
        }
        $CampoFormato->setAttributes([
            'opciones' => json_encode($data),
            'valor' => implode(';', $values)
        ]);
        $CampoFormato->update();
    }

    /**
     * Resuelve la clase a utilizar basado en la
     * etiqueta html del campo
     *
     * @param String $typeField
     * @return string|null
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    private function resolveClass(String $fieldType): ?string
    {
        $className = "App\\Bundles\\pqr\\Services\\controllers\\AddEditFormat\\fields\\$fieldType";
        if (class_exists($className)) {
            return $className;
        }
        return null;
    }

    /**
     * Obtiene los datos por defecto para la creacion o actualizacion de un campo del formulario
     *
     * @param PqrFormField $PqrFormField
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    private function getFormatFieldData(PqrFormField $PqrFormField): array
    {
        $fieldType = $PqrFormField->PqrHtmlField->type_saia;

        if (!$className = $this->resolveClass($fieldType)) {
            throw new \Exception("No se encontro la clase para el tipo {$fieldType}", 1);
        }
        $Fields = new $className($PqrFormField);

        return $Fields->getValues();
    }

    /**
     * Crea un nuevo campo del formulario
     *
     * @param PqrFormField $PqrFormField
     * @return self
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    private function createRecordInFormatFields(PqrFormField $PqrFormField): self
    {
        $id = CamposFormato::newRecord($this->getFormatFieldData($PqrFormField));
        $PqrFormField->setAttributes([
            'fk_campos_formato' => $id
        ]);
        $PqrFormField->update();

        return $this;
    }

    /**
     * Adiciona campos adicionales predeterminados
     * al formulario (sys_estado)
     *
     * @return self
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     * 
     */
    private function addOtherFields(): self
    {
        $fields = [
            'sys_estado' => [
                'formato_idformato' => $this->PqrForm->fk_formato,
                'fila_visible' => 0,
                'obligatoriedad' => 0,
                'orden' => 0,
                'nombre' => 'sys_estado',
                'etiqueta' => 'Estado de la PQR',
                'tipo_dato' => 'string',
                'longitud' => '30',
                'predeterminado' => FtPqr::ESTADO_PENDIENTE,
                'etiqueta_html' => 'Hidden',
                'acciones' => NULL,
                'placeholder' => 'Estado de la PQR',
                'listable' => 1,
                'opciones' => NULL,
                'ayuda' => NULL,
                'longitud_vis' => NULL
            ],
            'sys_tercero' => [
                'formato_idformato' => $this->PqrForm->fk_formato,
                'fila_visible' => 0,
                'obligatoriedad' => 0,
                'orden' => 0,
                'nombre' => 'sys_tercero',
                'etiqueta' => 'Destinatario de la Respuesta',
                'tipo_dato' => 'integer',
                'longitud' => '11',
                'predeterminado' => 0,
                'etiqueta_html' => 'Hidden',
                'acciones' => NULL,
                'placeholder' => 'Destinatario de la respuesta',
                'listable' => 1,
                'opciones' => NULL,
                'ayuda' => NULL,
                'longitud_vis' => NULL
            ],
            'sys_fecha_vencimiento' => [
                'formato_idformato' => $this->PqrForm->fk_formato,
                'fila_visible' => 0,
                'obligatoriedad' => 0,
                'orden' => 0,
                'nombre' => 'sys_fecha_vencimiento',
                'etiqueta' => 'Fecha vecimiento PQR',
                'tipo_dato' => 'datetime',
                'longitud' => NULL,
                'predeterminado' => NULL,
                'etiqueta_html' => 'Hidden',
                'acciones' => NULL,
                'placeholder' => NULL,
                'listable' => 1,
                'opciones' => '{"hoy":false,"tipo":"date"}',
                'ayuda' => NULL,
                'longitud_vis' => NULL
            ],
            'sys_fecha_terminado' => [
                'formato_idformato' => $this->PqrForm->fk_formato,
                'fila_visible' => 0,
                'obligatoriedad' => 0,
                'orden' => 0,
                'nombre' => 'sys_fecha_terminado',
                'etiqueta' => 'Fecha Terminacion PQR',
                'tipo_dato' => 'datetime',
                'longitud' => NULL,
                'predeterminado' => NULL,
                'etiqueta_html' => 'Hidden',
                'acciones' => NULL,
                'placeholder' => NULL,
                'listable' => 1,
                'opciones' => '{"hoy":false,"tipo":"date"}',
                'ayuda' => NULL,
                'longitud_vis' => NULL
            ],
            'sys_anonimo' => [
                'formato_idformato' => $this->PqrForm->fk_formato,
                'fila_visible' => 0,
                'obligatoriedad' => 0,
                'orden' => 0,
                'nombre' => 'sys_anonimo',
                'etiqueta' => 'Anonimo',
                'tipo_dato' => 'integer',
                'longitud' => 1,
                'predeterminado' => 0,
                'etiqueta_html' => 'Hidden',
                'acciones' => NULL,
                'placeholder' => NULL,
                'listable' => 1,
                'opciones' => '{"type":"hidden"}',
                'ayuda' => NULL,
                'longitud_vis' => NULL
            ]
        ];

        foreach ($fields as $name => $data) {
            if ($CamposFormato = CamposFormato::findByAttributes([
                'nombre' => $name,
                'formato_idformato' => $this->PqrForm->fk_formato
            ])) {
                $CamposFormato->setAttributes($data);
                $CamposFormato->update(true);
            } else {
                CamposFormato::newRecord($data);
            }
        }

        return $this;
    }

    /**
     * Actualiza un campo del formulario
     *
     * @param PqrFormField $PqrFormField
     * @return self
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    private function updateRecordInFormatFields(PqrFormField $PqrFormField): self
    {
        $CamposFormato = new CamposFormato($PqrFormField->fk_campos_formato);
        $CamposFormato->setAttributes($this->getFormatFieldData($PqrFormField));
        $CamposFormato->update(true);

        return $this;
    }
}
