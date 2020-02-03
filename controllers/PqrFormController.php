<?php

namespace Saia\Pqr\Controllers;

use Exception;
use Saia\Pqr\Models\PqrForm;
use Saia\core\DatabaseConnection;
use Saia\models\formatos\Formato;
use Saia\Pqr\Models\PqrFormField;
use Saia\controllers\SessionController;
use Saia\models\formatos\CampoOpciones;
use Saia\models\formatos\CamposFormato;
use Saia\controllers\generador\FormatGenerator;

class PqrFormController
{
    public $request;
    /**
     *
     * @var PqrForm
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public $PqrForm;

    public function __construct(array $request = null)
    {
        $this->request = $request;
    }

    /**
     * Obtiene el formulario activo
     *
     * @return object
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function index(): object
    {
        $Response = (object) [
            'success' => 1,
            'data' => []
        ];

        if ($PqrForm = PqrForm::getPqrFormActive()) {
            $Response->data = $PqrForm->getAttributes();
        };

        return $Response;
    }

    /**
     * Almacena un nuevo formulario
     *
     * @return object
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function store(): object
    {
        $Response = (object) [
            'success' => 1,
            'data' => []
        ];
        $params = $this->request['params'];

        $nameFormat = 'pqr';
        $defaultFields = [
            'fk_formato' => 0,
            'active' => 1,
            'name' => $nameFormat
        ];

        try {
            $conn = DatabaseConnection::beginTransaction();

            if (Formato::findByAttributes([
                'nombre' => $nameFormat
            ])) {
                $defaultFields['name'] = 'pqrsf';
            }

            $attributes = array_merge($params, $defaultFields);

            $PqrForm = new PqrForm();
            $PqrForm->setAttributes($attributes);
            $PqrForm->save();

            $conn->commit();
            $Response->data = $PqrForm->getAttributes();
        } catch (Exception $th) {
            $conn->rollBack();
            $Response->success = 0;
            $Response->message = $th->getMessage();
        }

        return $Response;
    }

    /**
     * Actualiza el formulario
     *
     * @return object
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function update(): object
    {
        $Response = (object) [
            'success' => 0
        ];

        $params = $this->request['params']['data'];
        $id = $this->request['params']['id'];

        try {
            $conn = DatabaseConnection::beginTransaction();

            $PqrForm = new PqrForm($id);
            $PqrForm->setAttributes($params);
            $PqrForm->update();

            $conn->commit();
            $Response->success = 1;
            $Response->data = $PqrForm->getAttributes();
        } catch (Exception $th) {
            $conn->rollBack();
            $Response->success = 0;
            $Response->message = $th->getMessage();
        }

        return $Response;
    }

    /**
     * publica o crea el formulario en el webservice
     *
     * @return object
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function publish(): object
    {
        $Response = (object) [
            'success' => 1,
            'data' => []
        ];

        try {
            $conn = DatabaseConnection::beginTransaction();

            $this->PqrForm = PqrForm::getPqrFormActive();
            // if ($this->PqrForm->fk_formato) {
            //     $this->updateForm();
            // } else {
            //     $this->createForm();
            // }

            // $FormatGenerator = new FormatGenerator($this->PqrForm->fk_formato);
            // $FormatGenerator->generate();

            $Web = new WebserviceGenerator($this->PqrForm->Formato);
            $Web->generate();

            $Response->data = $this->PqrForm->getAttributes();
            $conn->commit();
        } catch (\Throwable $th) {
            $conn->rollBack();
            $Response->success = 0;
            $Response->message = $th->getMessage();
        }

        return $Response;
    }

    /**
     * Crea el formulario
     *
     * @return void
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    protected function createForm(): void
    {
        $this->createRecordInFormat()
            ->addEditRecordsInFormatFields();
    }

    /**
     * Actualiza el formulario
     *
     * @return void
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    protected function updateForm(): void
    {
        $this->updateRecordInFormat()
            ->addEditRecordsInFormatFields();
    }

    /**
     * Obtiene los datos por defecto para la creacion del registro en Formato
     *
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    protected function getFormatDefaultData(): array
    {
        $name = $this->PqrForm->name;
        return  [
            'nombre' => $name,
            'etiqueta' => $this->PqrForm->label,
            'cod_padre' => 0,
            'contador_idcontador' => $this->PqrForm->fk_contador,
            'nombre_tabla' => "ft_{$name}",
            'ruta_mostrar' => "app/modules/back_pqr/formatos/{$name}/mostrar.php",
            'ruta_editar' => "app/modules/back_pqr/formatos/{$name}/editar.php",
            'ruta_adicionar' => "app/modules/back_pqr/formatos/{$name}/adicionar.php",
            'encabezado' => 1,
            'cuerpo' => '',
            'pie_pagina' => 0,
            'margenes' => '25,25,25,25',
            'orientacion' => 0,
            'papel' => 'Letter',
            'exportar' => 'mpdf',
            'funcionario_idfuncionario' => SessionController::getValue('idfuncionario'),
            'mostrar' => 1,
            'detalle' => 0,
            'font_size' => 11,
            'mostrar_pdf' => 0,
            'fk_categoria_formato' => 0,
            'funcion_predeterminada' => 0,
            'paginar' => 0,
            'pertenece_nucleo' => 0,
            'descripcion_formato' => 'Modulo de PQR',
            'version' => 1,
            'module' => 'pqr',
            'firma_digital' => 0,
            'tipo_edicion' => 0,
            'item' => 0
        ];
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
        $Formato->setAttributes($this->getFormatDefaultData());
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
                'etiqueta_html' => 'text'
            ],
            'textarea' => [
                'longitud' => 4000,
                'tipo_dato' => 'text',
                'etiqueta_html' => 'textarea_cke'
            ],
            'select' => [
                'longitud' => 255,
                'tipo_dato' => 'string',
                'etiqueta_html' => 'select'
            ],
            'radio' => [
                'longitud' => 255,
                'tipo_dato' => 'string',
                'etiqueta_html' => 'radio'
            ],
            'checkbox' => [
                'longitud' => 255,
                'tipo_dato' => 'string',
                'etiqueta_html' => 'checkbox'
            ]
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
            CamposFormato::FLAG_DESCRIPTION
        ];
        if ($PqrFormField->required) {
            //TODO: CAMBIAR ESTO POR EL CAMPO DESCRIPCION QUE SE VERA EN EL DOCUMENTO
            //PREGUNTAR A JORGE RAMIREZ
            if (!$this->flagDescripcion) {
                $actions[] = CamposFormato::FLAG_DESCRIPTION;
                $this->flagDescripcion = true;
            }
        }

        return [
            'formato_idformato' => $this->PqrForm->fk_formato,
            'autoguardado' => 0,
            'fila_visible' => 1,
            'obligatoriedad' => $PqrFormField->required,
            'orden' => $PqrFormField->order,
            'nombre' => $PqrFormField->name,
            'etiqueta' => $PqrFormField->label,
            'tipo_dato' => $configuration['tipo_dato'],
            'longitud' => $configuration['longitud'],
            'etiqueta_html' => $configuration['etiqueta_html'],
            'ayuda' => '-',
            'acciones' => implode(',', $actions),
            'placeholder' => $PqrFormField->getSetting()->placeholder,
            'listable' => 1,
            'opciones' => null
        ];
    }

    /**
     * Crea un nuevo campo del formulario
     *
     * @param PqrFormField $PqrFormField
     * @return void
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    protected function createRecordInFormatFields(PqrFormField $PqrFormField)
    {
        $id = CamposFormato::newRecord($this->getFormatFieldData($PqrFormField));
        $PqrFormField->setAttributes([
            'fk_campos_formato' => $id
        ]);
        $PqrFormField->update();

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
