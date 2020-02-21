<?php

namespace Saia\Pqr\Controllers;

use Exception;
use Saia\models\Contador;
use Saia\Pqr\Models\PqrForm;
use Saia\core\DatabaseConnection;
use Saia\models\formatos\Formato;
use Saia\Pqr\Models\PqrFormField;
use Saia\controllers\SessionController;
use Saia\models\formatos\CampoOpciones;
use Saia\models\formatos\CamposFormato;
use Saia\controllers\generador\FormatGenerator;
use Saia\Pqr\Models\PqrHtmlField;

class PqrFormController
{
    /**
     * Campos que seran utilizados como descripcion/detalle en el modulo 
     */
    const FIELDS_DESCRIPTION = [
        'sys_tipo',
        'sys_email'
    ];

    /**
     * Variable que contiene todo el request que llega de las peticiones
     *
     * @var array|null
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
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

        try {
            $conn = DatabaseConnection::beginTransaction();

            if (!$contador = Contador::findColumn('idcontador', [
                'nombre' => 'radicacion_entrada'
            ])) {
                throw new Exception("El contador Externo-Interno NO existe", 1);
            }

            $nameFormat = 'pqr';
            $defaultFields = [
                'fk_formato' => 0,
                'active' => 1,
                'name' => $nameFormat,
                'fk_contador' => $contador[0]
            ];

            if (Formato::findByAttributes([
                'nombre' => $nameFormat
            ])) {
                $defaultFields['name'] = 'pqrsf';
            }

            $attributes = array_merge($params, $defaultFields);

            $this->PqrForm = new PqrForm();
            $this->PqrForm->setAttributes($attributes);
            $this->PqrForm->save();

            $this->createSystemFields();

            $conn->commit();
            $Response->data = $this->PqrForm->getAttributes();
        } catch (Exception $th) {
            $conn->rollBack();
            $Response->success = 0;
            $Response->message = $th->getMessage();
        }

        return $Response;
    }

    /**
     * Crea los campos del sistema
     *
     * @param PqrForm $PqrForm
     * @return void
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     * 
     */
    protected function createSystemFields(): void
    {
        foreach ($this->getSystemFields() as  $field) {
            PqrFormField::newRecord($field);
        }
    }

    /**
     * Campos que siempre deben ir en el formulario
     *
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     * 
     * @throws Exception
     * 
     */
    protected function getSystemFields(): array
    {

        if ($record = PqrHtmlField::findColumn('id', ['type' => 'select'])) {
            $selectType = $record[0];
        } else {
            throw new Exception("No se encontro el tipo de campo Select", 1);
        }

        if ($record = PqrHtmlField::findColumn('id', ['type' => 'email'])) {
            $emailType = $record[0];
        } else {
            throw new Exception("No se encontro el tipo de campo Input", 1);
        }

        return [
            [
                'label' => 'Tipo',
                'name' => 'sys_tipo',
                'required' => 1,
                'system' => 1,
                'orden' => 2,
                'fk_pqr_html_field' => $selectType,
                'fk_pqr_form' => $this->PqrForm->getPK(),
                'setting' => json_encode([
                    'options' => [
                        'Petición',
                        'Queja',
                        'Reclamo',
                        'Sugerencia',
                        'Felicitación'
                    ]
                ])
            ],
            [
                'label' => 'E-mail',
                'name' => 'sys_email',
                'required' => 1,
                'system' => 1,
                'orden' => 3,
                'fk_pqr_html_field' => $emailType,
                'fk_pqr_form' => $this->PqrForm->getPK(),
                'setting' => json_encode([
                    'placeholder' => 'example@pqr.com'
                ])
            ]
        ];
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
            if ($this->PqrForm->fk_formato) {
                $this->updateForm();
            } else {
                $this->createForm();
            }

            $FormatGenerator = new FormatGenerator($this->PqrForm->fk_formato);
            $FormatGenerator->generate();
            $FormatGenerator->createModule();


            $Web = new WebservicePqr($this->PqrForm);
            $Web->generate();

            $Response->data = $this->PqrForm->getAttributes();
            $conn->commit();
        } catch (\Throwable $th) {
            var_dump($th);
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
     * 
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
            'cuerpo' => '{*showContent*}',
            'pie_pagina' => 0,
            'margenes' => '25,25,25,25',
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
            'autoguardado' => 0,
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
