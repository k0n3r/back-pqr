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
        return $this->findBy(['pqrForm' => $pqrFormId], ['orden' => 'ASC']);
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
     * Retorna los campos con show_report=1 de un formulario, con type_saia del campo HTML.
     */
    public function getReportFieldsData(int $pqrFormId): array
    {
        $fields = $this->createQueryBuilder('f')
            ->join('f.htmlField', 'h')
            ->addSelect('h')
            ->where('f.pqrForm = :id')
            ->andWhere('f.showReport = :show')
            ->setParameter('id', $pqrFormId)
            ->setParameter('show', true)
            ->orderBy('f.orden', 'ASC')
            ->getQuery()
            ->getResult();

        return array_map(static fn (PqrFormField $f): array => [
            'name'              => $f->getName(),
            'label'             => $f->getLabel(),
            'fk_campos_formato' => $f->getFkCamposFormato(),
            'type_saia'         => $f->getHtmlField()->getTypeSaia(),
        ], $fields);
    }

    /**
     * Retorna los campos de un formulario en el mismo formato que el legacy getDataAttributes():
     * array plano con fk_pqr_html_field expandido como sub-array.
     */
    public function getDataAttributesForForm(int $pqrFormId): array
    {
        $fields = $this->createQueryBuilder('f')
            ->join('f.htmlField', 'h')
            ->addSelect('h')
            ->where('f.pqrForm = :id')
            ->setParameter('id', $pqrFormId)
            ->orderBy('f.orden', 'ASC')
            ->getQuery()
            ->getResult();

        return array_map(static function (PqrFormField $f): array {
            $h = $f->getHtmlField();

            return [
                'id'                 => $f->getId(),
                'required'           => (int)$f->isRequired(),
                'anonymous'          => (int)$f->isAnonymous(),
                'show_report'        => (int)$f->isShowReport(),
                'required_anonymous' => (int)$f->isRequiredAnonymous(),
                'fk_pqr_html_field'  => [
                    'id'        => $h->getId(),
                    'uniq'      => (int)$h->isUniq(),
                    'active'    => (int)$h->isActive(),
                    'label'     => $h->getLabel(),
                    'type'      => $h->getType(),
                    'type_saia' => $h->getTypeSaia(),
                ],
                'fk_pqr_form'        => $f->getFkPqrForm(),
                'fk_campos_formato'  => $f->getFkCamposFormato(),
                'is_system'          => (int)$f->isSystem(),
                'orden'              => $f->getOrden(),
                'active'             => (int)$f->isActive(),
                'name'               => $f->getName(),
                'label'              => $f->getLabel(),
                'setting'            => $f->getSetting(),
            ];
        }, $fields);
    }
}
