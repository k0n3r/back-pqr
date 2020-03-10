<?php

namespace Saia\Pqr\Controllers\AddEditFormat;

use Saia\Pqr\Models\PqrForm;
use Saia\Pqr\formatos\pqr\FtPqr;
use Saia\models\formatos\Formato;
use Saia\Pqr\Models\PqrFormField;
use Saia\controllers\SessionController;
use Saia\models\formatos\CampoOpciones;
use Saia\models\formatos\CamposFormato;
use Saia\Pqr\Controllers\AddEditFormat\TAddEditFormat;

class FtPqrController implements IAddEditFormat
{

    use TAddEditFormat;

    /**
     * Campos que seran utilizados como descripcion/detalle en el modulo 
     */
    const FIELDS_DESCRIPTION = [
        'sys_tipo',
        'sys_email',
        'sys_estado'
    ];
    protected $PqrForm;

    public function __construct(PqrForm $PqrForm)
    {
        $this->PqrForm = $PqrForm;
    }

    public function createForm(): void
    {
        $this->createRecordInFormat()
            ->addEditRecordsInFormatFields()
            ->addOtherFields();
    }

    public function updateForm(): void
    {
        $this->updateRecordInFormat()
            ->addEditRecordsInFormatFields()
            ->addOtherFields();
    }

    public function generateForm(): void
    {
        $this->FormatGenerator($this->PqrForm->fk_formato);
    }

    /**
     * Obtiene los datos por defecto para la creacion del registro en Formato
     *
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     * 
     */
    protected function getFormatDefaultData(bool $edit = false): array
    {
        $name = $this->PqrForm->name;
        $data = [
            'nombre' => $name,
            'etiqueta' => $this->PqrForm->label,
            'cod_padre' => 0,
            'contador_idcontador' => $this->PqrForm->fk_contador,
            'nombre_tabla' => "ft_{$name}",
            'ruta_mostrar' => "app/modules/back_pqr/formatos/{$name}/mostrar.php",
            'ruta_editar' => "app/modules/back_pqr/formatos/{$name}/editar.php",
            'ruta_adicionar' => "app/modules/back_pqr/formatos/{$name}/adicionar.php",
            'ruta_buscar' => "app/modules/back_pqr/formatos/{$name}/buscar.php",
            'encabezado' => 1,
            'cuerpo' => '{*showContent*}{*mostrar_estado_proceso*}',
            'pie_pagina' => 0,
            'margenes' => '25,25,15,25',
            'orientacion' => 0,
            'papel' => 'Letter',
            'exportar' => 'mpdf',
            'funcionario_idfuncionario' => SessionController::getValue('idfuncionario'),
            'detalle' => 0,
            'font_size' => 11,
            'mostrar_pdf' => 0,
            'fk_categoria_formato' => '2,3',
            'funcion_predeterminada' => 0,
            'paginar' => 0,
            'pertenece_nucleo' => 0,
            'descripcion_formato' => 'Modulo de PQR',
            'version' => 1,
            'banderas' => 'e', //Aprobacion automatica
            'module' => 'pqr',
            'firma_digital' => 0,
            'tipo_edicion' => 0,
            'item' => 0,
            'class_name' => 'Saia\Pqr\Controllers\TaskEvents'
        ];

        if ($edit) {
            unset($data['funcionario_idfuncionario']);
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
    protected function createRecordInFormat(): self
    {
        $id = Formato::newRecord($this->getFormatDefaultData());

        $this->PqrForm->setAttributes([
            'fk_formato' => $id
        ]);
        $this->PqrForm->update();

        return $this;
    }

    /**
     * Actualiza el registro en Formato
     *
     * @return self
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    protected function updateRecordInFormat(): self
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
    protected function addEditRecordsInFormatFields(): self
    {
        $fields = $this->PqrForm->PqrFormFields;
        foreach ($fields as $PqrFormField) {
            if (!$PqrFormField->fk_campos_formato) {
                $this->createRecordInFormatFields($PqrFormField);
            } else {
                $this->updateRecordInFormatFields($PqrFormField);
            }

            if ($PqrFormField->getSetting()->options) {
                $this->addEditformatOptions($PqrFormField);
            }
        }
        return $this;
    }

    /**
     * Obtiene la configuracion de los campos del formulario
     *
     * @param string $type
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    protected function defaultConfigurationOfFormField(string $type): array
    {
        $typeField = [
            'input' => [
                'longitud' => 255,
                'tipo_dato' => 'string',
                'etiqueta_html' => 'text',
                'opciones' => NULL
            ],
            'textarea' => [
                'longitud' => 4000,
                'tipo_dato' => 'text',
                'etiqueta_html' => 'textarea_cke',
                'opciones' => NULL
            ],
            'select' => [
                'longitud' => 255,
                'tipo_dato' => 'string',
                'etiqueta_html' => 'select',
                'opciones' => NULL
            ],
            'radio' => [
                'longitud' => 255,
                'tipo_dato' => 'string',
                'etiqueta_html' => 'radio',
                'opciones' => NULL
            ],
            'checkbox' => [
                'longitud' => 255,
                'tipo_dato' => 'string',
                'etiqueta_html' => 'checkbox',
                'opciones' => NULL
            ],
            'email' => [
                'longitud' => 255,
                'tipo_dato' => 'string',
                'etiqueta_html' => 'text',
                'opciones' => '{"type":"email"}'
            ],

        ];
        return $typeField[$type];
    }

    /**
     * Crea o edita las opciones de los campos tipo select, radio o checkbxo
     *
     * @param PqrFormField $PqrFormField
     * @return void
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    protected function addEditformatOptions(PqrFormField $PqrFormField): void
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

        $data = [];

        foreach ($PqrFormField->getSetting()->options as $option) {

            if ($CampoOpciones = CampoOpciones::findByAttributes([
                'valor' => $option,
                'fk_campos_formato' => $CampoFormato->getPk()
            ])) {
                $CampoOpciones->setAttributes([
                    'estado' => 1
                ]);
                $CampoOpciones->update();
                $id = $CampoOpciones->llave;
            } else {
                $id = $llave + 1;
                $llave = $id;
                CampoOpciones::newRecord([
                    'llave' => $id,
                    'valor' => $option,
                    'fk_campos_formato' => $CampoFormato->getPK(),
                    'estado' => 1
                ]);
            }

            $data[] = [
                'llave' => $id,
                'item' => $option
            ];
        }
        $CampoFormato->setAttributes([
            'opciones' => json_encode($data)
        ]);
        $CampoFormato->update();
    }

    /**
     * Obtiene los datos por defecto para la creacion o actualizacion de un campo del formulario
     *
     * @param PqrFormField $PqrFormField
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    protected function getFormatFieldData(PqrFormField $PqrFormField): array
    {
        $configuration = $this->defaultConfigurationOfFormField($PqrFormField->PqrHtmlField->type);

        $actions = [
            CamposFormato::FLAG_ADD,
            CamposFormato::FLAG_EDIT
        ];

        if ($PqrFormField->required) {
            if (in_array($PqrFormField->name, self::FIELDS_DESCRIPTION)) {
                $actions[] = CamposFormato::FLAG_DESCRIPTION;
            }
        }

        return [
            'formato_idformato' => $this->PqrForm->fk_formato,
            'fila_visible' => 1,
            'obligatoriedad' => $PqrFormField->required,
            'orden' => $PqrFormField->orden,
            'nombre' => $PqrFormField->name,
            'etiqueta' => $PqrFormField->label,
            'tipo_dato' => $configuration['tipo_dato'],
            'longitud' => $configuration['longitud'],
            'etiqueta_html' => $configuration['etiqueta_html'],
            'acciones' => implode(',', $actions),
            'placeholder' => $PqrFormField->getSetting()->placeholder,
            'listable' => 1,
            'opciones' => $configuration['opciones'],
            'ayuda' => NULL,
            'longitud_vis' => NULL
        ];
    }

    /**
     * Crea un nuevo campo del formulario
     *
     * @param PqrFormField $PqrFormField
     * @return self
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    protected function createRecordInFormatFields(PqrFormField $PqrFormField): self
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
    protected function addOtherFields(): self
    {
        $data = [
            'formato_idformato' => $this->PqrForm->fk_formato,
            'fila_visible' => 0,
            'obligatoriedad' => 0,
            'orden' => 0,
            'nombre' => 'sys_estado',
            'etiqueta' => 'Estado de la PQR',
            'tipo_dato' => 'string',
            'longitud' => '30',
            'predeterminado' => FtPqr::ESTADO_PENDIENTE,
            'etiqueta_html' => 'hidden',
            'acciones' => NULL,
            'placeholder' => 'Estado de la PQR',
            'listable' => 1,
            'opciones' => NULL,
            'ayuda' => NULL,
            'longitud_vis' => NULL
        ];

        if ($CamposFormato = CamposFormato::findByAttributes([
            'nombre' => 'sys_estado',
            'formato_idformato' => $this->PqrForm->fk_formato
        ])) {
            $CamposFormato->setAttributes($data);
            $CamposFormato->update(true);
        } else {
            CamposFormato::newRecord($data);
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
    protected function updateRecordInFormatFields(PqrFormField $PqrFormField): self
    {
        $CamposFormato = new CamposFormato($PqrFormField->fk_campos_formato);
        $CamposFormato->setAttributes($this->getFormatFieldData($PqrFormField));
        $CamposFormato->update(true);

        return $this;
    }
}
