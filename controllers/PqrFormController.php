<?php

namespace Saia\Pqr\Controllers;

use Exception;
use Saia\Pqr\Models\PqrForm;
use Saia\core\DatabaseConnection;
use Saia\models\formatos\Formato;
use Saia\controllers\SessionController;

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

        if ($PqrForm = PqrForm::findAllByAttributes()) {
            $Response->data = $PqrForm[0]->getAttributes();
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

            if ($PqrForm->save()) {
                $conn->commit();
                $Response->data = $PqrForm->getAttributes();
            } else {
                throw new Exception("No fue posible guardar", 1);
            }
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

            if ($PqrForm->update()) {
                $conn->commit();
                $Response->success = 1;
                $Response->data = $PqrForm->getAttributes();
            } else {
                throw new Exception("No fue posible eliminar", 1);
            }
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

            $id = $this->request['id'] ? $this->request['id'] : null;
            $this->PqrForm = new PqrForm($id);

            if ($id) {
                $this->updateForm();
            } else {
                $this->createForm();
            }

            $Response->data = $this->PqrForm->getAttributes();
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
        if ($idform = $this->newRecordFormat()) {
            $this->PqrForm->setAttributes([
                'fk_formato' => $idform
            ]);
            if ($this->newRecordFormatField($idform)) {
            } else {
                throw new Exception("No fue posible registrar  los campos del formulario", 1);
            }
        } else {
            throw new Exception("No fue posible registrar el formulario", 1);
        }
    }

    protected function newRecordFormat()
    {
        return Formato::newRecord([
            'nombre' => 'pqr',
            'etiqueta' => 'PQR',
            'cod_padre' => 0,
            'contador_idcontador' => 0,
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
            'module' => 'pqr'
        ]);
    }

    protected function newRecordFormatField(int $idform)
    {
    }
}
