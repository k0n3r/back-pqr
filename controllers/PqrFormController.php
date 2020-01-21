<?php

namespace Saia\Pqr\Controllers;

use Exception;
use Saia\controllers\SessionController;
use Saia\core\DatabaseConnection;
use Saia\models\formatos\CamposFormato;
use Saia\models\formatos\Formato;
use Saia\Pqr\Models\PqrForm;
use Saia\Pqr\Models\PqrFormField;

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

    public function index(): object
    {
        $Response = (object) [
            'success' => 1,
            'data' => []
        ];

        if ($PqrForm = PqrForm::findByAttributes(['active' => 1])) {
            $Response->data = $PqrForm->getAttributes();
        };

        return $Response;
    }

    public function store(): object
    {
        $Response = (object) [
            'success' => 1,
            'data' => []
        ];
        $params = $this->request['params'];

        $defaultFields = [
            'fk_formato' => 0,
            'active' => 1,
        ];

        try {
            $conn = DatabaseConnection::beginTransaction();

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

    public function update()
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

    public function publish(): object
    {
        $Response = (object) [
            'success' => 1,
            'data' => []
        ];

        try {
            $conn = DatabaseConnection::beginTransaction();



            $PqrFormField = new PqrFormField(14);
            $PqrFormField->setAttributes([
                'fk_campos_formato' => 15
            ]);
            $PqrFormField->update();
            var_dump($PqrFormField, $PqrFormField->getAttributes());

            /*$id = $this->request['id'] ? $this->request['id'] : null;
            $this->PqrForm = new PqrForm($id);

            if ($this->PqrForm->fk_formato) {
                $this->updateForm();
            } else {
                $this->createForm();
            }

            $Response->data = $this->PqrForm->getAttributes();*/
            $conn->commit();
        } catch (\Throwable $th) {
            $conn->rollBack();
            $Response->success = 0;
            $Response->message = $th->getMessage();
        }

        return $Response;
    }

    protected function updateForm(): void
    {
    }

    protected function createForm(): void
    {
        $this->createRecordInFormat()
            ->addEditRecordsInFormatFields();
    }

    protected function createRecordInFormat(): self
    {
        $id = Formato::newRecord([
            'nombre' => 'pqr',
            'etiqueta' => $this->PqrForm->label,
            'cod_padre' => 0,
            'contador_idcontador' => $this->PqrForm->fk_contador,
            'nombre_tabla' => 'ft_pqr',
            'ruta_mostrar' => 'app/modules/back_pqr/formatos/pqr/mostrar.php',
            'ruta_editar' => 'app/modules/back_pqr/formatos/pqr/editar.php',
            'ruta_adicionar' => 'app/modules/back_pqr/formatos/pqr/mostrar.php',
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
            'firma_digital' => 0
        ]);

        $this->PqrForm->setAttributes([
            'fk_formato' => $id
        ]);
        $this->PqrForm->update();

        return $this;
    }

    protected function addEditRecordsInFormatFields(): self
    {
        $fields = $this->PqrForm->PqrFormFields;
        foreach ($fields as $PqrFormField) {
            if (!$PqrFormField->fk_campos_formato) {
                $this->createRecordInFormatFields($PqrFormField);
            } else {
                $this->updateRecordInFormatFields($PqrFormField);
            }
        }
        return $this;
    }

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
                'etiqueta_html' => 'text'
            ],
            'radio' => [
                'longitud' => 255,
                'tipo_dato' => 'string',
                'etiqueta_html' => 'text'
            ],
            'checkbox' => [
                'longitud' => 255,
                'tipo_dato' => 'string',
                'etiqueta_html' => 'text'
            ]
        ];
        return $typeField[$type];
    }

    protected function createRecordInFormatFields(PqrFormField $PqrFormField)
    {
        $configuration = $this->defaultConfigurationOfFormField($PqrFormField->PqrHtmlField->type);

        $id = CamposFormato::newRecord([
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
            'acciones' => 'a,e',
            'placeholder' => $PqrFormField->getSetting()->placeholder,
            'listable' => 1
        ]);

        $PqrFormField->setAttributes([
            'fk_campos_formato' => $id
        ]);
        var_dump($PqrFormField, $PqrFormField->getAttributes());
        die();
        //$PqrFormField->update();

        return $this;
    }

    protected function updateRecordInFormatFields(PqrFormField $PqrFormField): self
    {
        return $this;
    }
}
