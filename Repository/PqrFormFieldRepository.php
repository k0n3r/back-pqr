<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Repository;

use App\Bundles\pqr\Entity\PqrFormField;
use App\Repository\BaseRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<PqrFormField>
 */
class PqrFormFieldRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PqrFormField::class);
    }

    /**
     * @return PqrFormField[]
     */
    public function findByPqrFormOrdered(int $pqrFormId): array
    {
        return $this->findBy(['fkPqrForm' => $pqrFormId], ['orden' => 'ASC']);
    }

    public function findByName(string $name): ?PqrFormField
    {
        return $this->findOneBy(['name' => $name]);
    }

    public function findSysTipo(): ?PqrFormField
    {
        return $this->findByName(PqrFormField::FIELD_NAME_SYS_TIPO);
    }

    /**
     * Retorna los campos de un formulario en el mismo formato que el legacy getDataAttributes():
     * array plano con fk_pqr_html_field expandido como sub-array.
     */
    public function getDataAttributesForForm(int $pqrFormId): array
    {
        $rows = $this->getEntityManager()->getConnection()
            ->createQueryBuilder()
            ->select(
                'f.id', 'f.required', 'f.anonymous', 'f.show_report', 'f.required_anonymous',
                'f.fk_pqr_html_field', 'f.fk_pqr_form', 'f.fk_campos_formato',
                'f.is_system', 'f.orden', 'f.active', 'f.name', 'f.label', 'f.setting',
                'h.id as html_id', 'h.uniq', 'h.active as html_active',
                'h.label as html_label', 'h.type', 'h.type_saia',
            )
            ->from('pqr_form_fields', 'f')
            ->join('f', 'pqr_html_fields', 'h', 'h.id = f.fk_pqr_html_field')
            ->where('f.fk_pqr_form = :id')
            ->setParameter('id', $pqrFormId)
            ->orderBy('f.orden', 'ASC')
            ->executeQuery()
            ->fetchAllAssociative();

        return array_map(static function (array $row): array {
            return [
                'id'                 => (int)$row['id'],
                'required'           => (int)$row['required'],
                'anonymous'          => (int)$row['anonymous'],
                'show_report'        => (int)$row['show_report'],
                'required_anonymous' => (int)$row['required_anonymous'],
                'fk_pqr_html_field'  => [
                    'id'        => (int)$row['html_id'],
                    'uniq'      => (int)$row['uniq'],
                    'active'    => (int)$row['html_active'],
                    'label'     => $row['html_label'],
                    'type'      => $row['type'],
                    'type_saia' => $row['type_saia'],
                ],
                'fk_pqr_form'        => (int)$row['fk_pqr_form'],
                'fk_campos_formato'  => (int)$row['fk_campos_formato'],
                'is_system'          => (int)$row['is_system'],
                'orden'              => (int)$row['orden'],
                'active'             => (int)$row['active'],
                'name'               => $row['name'],
                'label'              => $row['label'],
                'setting'            => $row['setting'],
            ];
        }, $rows);
    }
}
