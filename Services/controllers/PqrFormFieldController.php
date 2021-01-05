<?php

namespace App\Bundles\pqr\Services\controllers;

use Exception;

use App\Bundles\pqr\Services\models\PqrForm;

use Doctrine\DBAL\Types\Type;
use Saia\controllers\generator\FormatGenerator;
use Saia\core\DatabaseConnection;

use App\Bundles\pqr\Services\models\PqrFormField;
use App\Bundles\pqr\Services\PqrFormService;

class PqrFormFieldController extends Controller
{
    /**
     * Bandera que indica el numero minimo donde empezara el orden de los campos
     */
    const INITIAL_ORDER = 2;

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

        if ($this->request['setting']) {
            $this->request['setting'] = json_encode($this->request['setting']);
        }

        $PqrForm = new PqrForm($this->request['fk_pqr_form']);
        $defaultFields = [
            'name' => $this->generateName($this->request['label']),
            'required' => 0,
            'show_anonymous' => 0,
            'fk_pqr_form' => $PqrForm->getPK(),
            'fk_campos_formato' => 0,
            'is_system' => 0,
            'orden' => ($PqrForm->countFields()) + self::INITIAL_ORDER,
            'active' => 1
        ];

        try {
            $conn = DatabaseConnection::getDefaultConnection();
            $conn->beginTransaction();

            $attributes = array_merge($defaultFields, $this->request);

            $PqrFormField = new PqrFormField();
            $PqrFormField->setAttributes($attributes);

            if ($PqrFormField->save()) {
                $conn->commit();
                $Response->data = $PqrFormField->getDataAttributes();
            } else {
                throw new Exception("No fue posible guardar", 200);
            }
        } catch (Exception $th) {
            $conn->rollBack();
            $Response->success = 0;
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

        $id = $this->request['id'];
        $requestFormField = $this->request['dataField'];

        if ($requestFormField['setting']) {
            $requestFormField['setting'] = json_encode($requestFormField['setting']);
        }

        try {
            $conn = DatabaseConnection::getDefaultConnection();
            $conn->beginTransaction();

            $PqrFormField = new PqrFormField($id);
            $PqrFormField->setAttributes($requestFormField);

            if ($PqrFormField->update()) {
                $conn->commit();
                $Response->success = 1;
                $Response->data = $PqrFormField->getDataAttributes();
            } else {
                throw new Exception("No fue posible actualizar", 200);
            }
        } catch (Exception $th) {
            $conn->rollBack();
            $Response->message = $th->getMessage();
        }

        return $Response;
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
            $conn = DatabaseConnection::getDefaultConnection();
            $conn->beginTransaction();

            $PqrFormField = new PqrFormField($this->request['id']);
            if ($PqrFormField->delete()) {
                if ($PqrFormField->fk_campos_formato) {
                    if (!$PqrFormField->CamposFormato->delete()) {
                        throw new Exception("No fue posible eliminar el campo", 200);
                    }
                }
                $conn->commit();
                $Response->success = 1;
            } else {
                throw new Exception("No fue posible eliminar", 200);
            }
        } catch (Exception $th) {
            $conn->rollBack();
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
    private function generateName(string $label, int $pref = 0): string
    {
        $label = $this->excludeReservedWords($label);
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

        if ($this->columnExistsDB($name)) {
            $pref++;
            $name = $this->generateName($name, $pref);
        }

        return $name;
    }

    private function excludeReservedWords(string $label): string
    {
        $reservedWords = [
            'select', 'from', 'where', 'uniq', 'numero', 'fecha'
        ];

        return in_array($label, $reservedWords) ? $label . "_" : $label;
    }

    private function columnExistsDB($name): bool
    {
        $schema = DatabaseConnection::getInstance()->getSchemaManager();
        $Table = $schema->listTableDetails('ft_pqr');

        return $Table->hasColumn($name);
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
            $conn = DatabaseConnection::getDefaultConnection();
            $conn->beginTransaction();

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

    public function updateShowReport()
    {
        $Response = (object) [
            'success' => 0
        ];

        try {
            $conn = DatabaseConnection::getDefaultConnection();
            $conn->beginTransaction();

            DatabaseConnection::getDefaultConnection()
                ->createQueryBuilder()
                ->update('pqr_form_fields')
                ->set('show_report', 0)->execute();

            if ($this->request['ids']) {
                foreach ($this->request['ids'] as $id) {
                    $PqrFormField = new PqrFormField($id);
                    $PqrFormField->show_report = 1;
                    if (!$PqrFormField->update()) {
                        throw new \Exception("No fue posible actualizar", 200);
                    };
                }
            }

            (new PqrFormController())->generaReport();

            $PqrFormService = new PqrFormService(PqrForm::getPqrFormActive());
            $Response->pqrFormFields = $PqrFormService->getDataPqrFormFields();

            $Response->success = 1;
            $conn->commit();
        } catch (\Exception $th) {
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
            $conn = DatabaseConnection::getDefaultConnection();
            $conn->beginTransaction();

            $PqrFormField = new PqrFormField($this->request['id']);
            $PqrFormField->setAttributes([
                'active' => (int) $this->request['active'],
                'required' => 0,
                'required_anonymous' => 0
            ]);

            if ($PqrFormField->name != 'sys_subtipo' && $PqrFormField->name != 'sys_dependencia') {
                $PqrFormField->show_report = 0;
            }

            if (!$PqrFormField->update()) {
                throw new Exception("No fue posible actualizar el campo", 200);
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

    /**
     * Obtiene una lista de datos
     *
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function getList(): array
    {
        $list = [];
        switch ($this->request['type']) {
            case 'dependencia':
                $records = $this->getListDependency();
                break;
            case 'pais':
                $records = $this->getListPais();
                break;
            case 'departamento':
                $records = $this->getListDepartamento();
                break;
        }

        foreach ($records as $row) {
            $list[] = [
                'id' => $row['id'],
                'text' => $row['nombre']
            ];
        }

        return ['results' => $list];
    }

    /**
     * Obtiene una lista de dependencias
     *
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    protected function getListDependency(): array
    {
        $Qb = DatabaseConnection::getDefaultConnection()
            ->createQueryBuilder()
            ->select('iddependencia as id,nombre')
            ->from('dependencia')
            ->where('estado=1')
            ->orderBy('nombre', 'ASC')
            ->setFirstResult(0)
            ->setMaxResults(40);

        if ($this->request['term']) {
            $Qb->andWhere('nombre like :nombre')
                ->setParameter(':nombre', '%' . $this->request['term'] . '%', Type::getType('string'));
        }
        return $Qb->execute()->fetchAll();
    }

    /**
     * Obtiene una lista de paises
     *
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    protected function getListPais(): array
    {
        $Qb = DatabaseConnection::getDefaultConnection()
            ->createQueryBuilder()
            ->select('idpais as id,nombre')
            ->from('pais')
            ->where('estado=1')
            ->orderBy('nombre', 'ASC')
            ->setFirstResult(0)
            ->setMaxResults(40);

        if ($this->request['term']) {
            $Qb->andWhere('nombre like :nombre')
                ->setParameter(':nombre', '%' . $this->request['term'] . '%', Type::getType('string'));
        }
        return $Qb->execute()->fetchAll();
    }

    /**
     * Obtiene una lista de departamentos
     *
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    protected function getListDepartamento(): array
    {
        $Qb = DatabaseConnection::getDefaultConnection()
            ->createQueryBuilder()
            ->select('iddepartamento as id,nombre')
            ->from('departamento')
            ->where('estado=1')
            ->orderBy('nombre', 'ASC')
            ->setFirstResult(0)
            ->setMaxResults(40);

        if ($this->request['idpais']) {
            $Qb->andWhere('pais_idpais=:pais')
                ->setParameter(':pais', $this->request['idpais'], Type::getType('integer'));
        }

        if ($this->request['term']) {
            $Qb->andWhere('nombre like :nombre')
                ->setParameter(':nombre', '%' . $this->request['term'] . '%', Type::getType('string'));
        }

        return $Qb->execute()->fetchAll();
    }

    /**
     * Obtiene los campos que se podran utilizar para la
     * carga automatica del destino de la respuesta
     *
     * @return object
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function getTextFields(): object
    {
        $Qb = DatabaseConnection::getDefaultConnection()
            ->createQueryBuilder()
            ->select('ff.*')
            ->from('pqr_form_fields', 'ff')
            ->join('ff', 'pqr_html_fields', 'hf', 'ff.fk_pqr_html_field=hf.id')
            ->where("hf.type_saia='Text' and ff.active=1")
            ->orderBy('ff.orden');

        $data = [];
        if ($records = PqrFormField::findByQueryBuilder($Qb)) {
            foreach ($records as $PqrFormField) {
                $data[] = [
                    'id' => $PqrFormField->getPK(),
                    'text' => $PqrFormField->label
                ];
            }
        }

        return (object) [
            'success' => 1,
            'data' => $data
        ];
    }
}