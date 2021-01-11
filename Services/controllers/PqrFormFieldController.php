<?php

namespace App\Bundles\pqr\Services\controllers;

use App\Bundles\pqr\Services\models\PqrForm;
use Doctrine\DBAL\Types\Type;
use Saia\core\DatabaseConnection;
use App\Bundles\pqr\Services\models\PqrFormField;
use App\Bundles\pqr\Services\PqrFormService;

class PqrFormFieldController extends Controller
{
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
}
