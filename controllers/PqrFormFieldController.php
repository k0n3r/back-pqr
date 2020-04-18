<?php

namespace Saia\Pqr\Controllers;

use Exception;
use Saia\core\DatabaseConnection;
use Saia\Pqr\Models\PqrForm;
use Saia\Pqr\Models\PqrFormField;

class PqrFormFieldController
{
    /**
     * Bandera que indica el numero minimo donde empezara el orden de los campos
     */
    const INITIAL_ORDER = 2;

    /**
     * Variable que contiene todo el request que llega de las peticiones
     *
     * @var array|null
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public $request;

    public function __construct(array $request = null)
    {
        $this->request = $request;
    }

    /**
     * Obtiene los campos del formulario activos
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

        $Instances = PqrFormField::findAllByAttributes([
            //'active' => 1
        ], [], 'orden asc');

        $data = [];
        foreach ($Instances as $Instance) {
            $data[] = $Instance->getDataAttributes();
        }
        $Response->data = $data;

        return $Response;
    }

    /**
     * Almacena un nuevo campo del formulario
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

        $PqrForm = new PqrForm($params['fk_pqr_form']);
        $cant = $PqrForm->countFields();

        $defaultFields = [
            'name' => $this->generateName($params['label']),
            'active' => 1,
            'setting' => json_encode($params['setting']),
            'fk_pqr_form' => $PqrForm->getPK(),
            'orden' => $cant + self::INITIAL_ORDER,
            'fk_campos_formato' => 0,
            'system' => 0
        ];

        try {
            $conn = DatabaseConnection::beginTransaction();

            $attributes = array_merge($params, $defaultFields);

            $PqrFormField = new PqrFormField();
            $PqrFormField->setAttributes($attributes);

            if ($PqrFormField->save()) {
                $conn->commit();
                $Response->data = $PqrFormField->getDataAttributes();
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

    /**
     * genera un nombre unico para el campo del formulario
     *
     * @param string $label
     * @param integer $pref
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    protected function generateName(string $label, int $pref = 0): string
    {
        $cadena = trim(preg_replace('/[^a-z]/', '_', strtolower($label)));
        $cadena = implode('_', array_filter(explode('_', $cadena)));
        $cadena = trim(substr($cadena, 0, 20), '_');

        $name = $pref ? "{$cadena}_{$pref}" : $cadena;

        if (PqrFormField::findAllByAttributes([
            'name' => $name
        ])) {
            $pref++;
            $name = $this->generateName($name, $pref);
        }
        return $name;
    }

    /**
     * Elimina un campo del formulario
     *
     * @return object
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function destroy(): object
    {
        $Response = (object) [
            'success' => 0
        ];

        try {
            $conn = DatabaseConnection::beginTransaction();

            $PqrFormField = new PqrFormField($this->request['id']);
            if ($PqrFormField->delete()) {
                $conn->commit();
                $Response->success = 1;
            } else {
                throw new Exception("No fue posible eliminar", 1);
            }
        } catch (Exception $th) {
            $conn->rollBack();
            $Response->message = $th->getMessage();
        }

        return $Response;
    }

    /**
     * Actualiza un campo del formulario
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

        $params = $this->request['params']['dataField'];
        $id = $this->request['params']['id'];

        $params['setting'] = json_encode($params['setting']);

        try {
            $conn = DatabaseConnection::beginTransaction();

            $PqrFormField = new PqrFormField($id);
            $PqrFormField->setAttributes($params);

            if ($PqrFormField->update()) {
                $conn->commit();
                $Response->success = 1;
                $Response->data = $PqrFormField->getDataAttributes();
            } else {
                throw new Exception("No fue posible actualizar", 1);
            }
        } catch (Exception $th) {
            $conn->rollBack();
            $Response->message = $th->getMessage();
        }

        return $Response;
    }

    /**
     * Actualiza el orden de los campos
     *
     * @return object
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function updateOrder(): object
    {
        $Response = (object) [
            'success' => 0
        ];

        try {
            $conn = DatabaseConnection::beginTransaction();

            foreach ($this->request['params'] as $record) {
                $PqrFormField = new PqrFormField($record['id']);
                $PqrFormField->setAttributes([
                    'orden' => $record['order'] + self::INITIAL_ORDER
                ]);
                $PqrFormField->update();
            }
            $conn->commit();
            $Response->success = 1;
        } catch (Exception $th) {
            $conn->rollBack();
            $Response->message = $th->getMessage();
        }
        return $Response;
    }

    /**
     * Actualiza el estado(active) del campo
     *
     * @return object
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function updateActive(): object
    {
        $Response = (object) [
            'success' => 0
        ];

        try {
            $conn = DatabaseConnection::beginTransaction();
            $params = $this->request['params'];

            $PqrFormField = new PqrFormField($params['id']);
            $PqrFormField->setAttributes([
                'active' => (int) $params['active']
            ]);

            if (!$PqrFormField->update()) {
                throw new Exception("No fue posible actualizar el campo", 1);
            }
            $Response->data = $PqrFormField->getDataAttributes();

            $conn->commit();
            $Response->success = 1;
        } catch (Exception $th) {
            $conn->rollBack();
            $Response->message = $th->getMessage();
        }
        return $Response;
    }
}
