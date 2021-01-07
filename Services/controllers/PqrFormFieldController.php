<?php

namespace App\Bundles\pqr\Services\controllers;

use App\Bundles\pqr\Services\models\PqrForm;
use Doctrine\DBAL\Types\Type;
use Saia\core\DatabaseConnection;
use App\Bundles\pqr\Services\models\PqrFormField;
use App\Bundles\pqr\Services\PqrFormService;

class PqrFormFieldController extends Controller
{

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
