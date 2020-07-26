<?php

namespace Saia\Pqr\controllers\services;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use Saia\core\DatabaseConnection;
use Saia\Pqr\models\PqrFormField;
use Saia\Pqr\models\PqrHtmlField;

class PqrFormFieldService
{

    private PqrFormField $PqrFormField;

    public function __construct(PqrFormField $PqrFormField)
    {
        $this->PqrFormField = $PqrFormField;
    }

    /**
     * Obtiene la instancia de PqrFormField actualizada
     *
     * @return PqrFormField
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function getModel(): PqrFormField
    {
        return $this->PqrFormField;
    }

    /**
     * Retorna listado de valores
     *
     * @param array $data
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function getListField(array $data = []): array
    {
        $list = [];

        switch ($this->PqrFormField->PqrHtmlField->type) {
            case PqrHtmlField::TYPE_DEPENDENCIA:
                $list = $this->getDependencys($this->PqrFormField->getSetting(), $data);
                break;

            case PqrHtmlField::TYPE_LOCALIDAD:
                $list = $this->getListLocalidad($this->PqrFormField->getSetting(), $data);
                break;
        }

        return $list;
    }


    /**
     * Obtiene listado de localidades basados en la configuracion
     * del campo
     *
     * @param object $ObjSettings
     * @param array $data
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    private function getDependencys(object $ObjSettings, array $data = []): array
    {
        $Qb = DatabaseConnection::getDefaultConnection()
            ->createQueryBuilder();

        $Qb->select('iddependencia as id,nombre as text')
            ->from('dependencia')
            ->where('estado=1')
            ->orderBy('nombre', 'ASC')
            ->setFirstResult(0)
            ->setMaxResults(40);

        if ($data['id']) {
            $Qb->andWhere('iddependencia=:iddependencia')
                ->setParameter(':iddependencia', $data['id'], Type::getType('integer'));

            return $Qb->execute()->fetchAll();
        }

        if ($data['term']) {
            $Qb->andWhere('nombre like :nombre')
                ->setParameter(':nombre', '%' . $data['term'] . '%', Type::getType('string'));
        }
        if ($data['id']) {
            $Qb->andWhere('nombre like :nombre')
                ->setParameter(':nombre', '%' . $data['term'] . '%', Type::getType('string'));
        }

        if (!$ObjSettings->allDependency) {
            $records = $ObjSettings->options;
            foreach ($records as $row) {
                $ids[] = $row->id;
            }
            $Qb->andWhere('iddependencia in (:ids)')
                ->setParameter(':ids', $ids, Connection::PARAM_INT_ARRAY);
        }

        return $Qb->execute()->fetchAll();
    }

    /**
     * Obtiene las localidades basados en la configuracion
     * del campo
     *
     * @param object $ObjSettings
     * @param array $data
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    private function getListLocalidad(object $ObjSettings, array $data = []): array
    {
        $Qb = DatabaseConnection::getDefaultConnection()
            ->createQueryBuilder();

        $Qb->select("CONCAT(a.nombre,
            CONCAT(
                ' - ',
                CONCAT(
                    b.nombre,
                    CONCAT(
                        ' - ',
                        c.nombre   
                    )
                )
            )
        ) AS text", "a.idmunicipio as id")
            ->from('municipio', 'a')
            ->join('a', 'departamento', 'b', 'a.departamento_iddepartamento = b.iddepartamento')
            ->join('b', 'pais', 'c', 'b.pais_idpais = c.idpais')
            ->where("CONCAT(a.nombre,CONCAT(' ',b.nombre)) like :query")
            ->andWhere('a.estado = 1 AND b.estado = 1 AND c.estado = 1')
            ->setParameter('query', "%{$data['term']}%")
            ->orderBy('a.nombre', 'ASC')
            ->setFirstResult(0)
            ->setMaxResults(40);

        if ($data['id']) {
            $Qb->andWhere('idmunicipio=:idmunicipio')
                ->setParameter(':idmunicipio', $data['id'], Type::getType('integer'));

            return $Qb->execute()->fetchAll();
        }

        if (!$ObjSettings->allCountry) {
            $Qb->andWhere('c.idpais=:idpais')
                ->setParameter(':idpais', $ObjSettings->country->id);
        }

        return $Qb->execute()->fetchAll();
    }
}
