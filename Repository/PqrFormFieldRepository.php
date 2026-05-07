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
}
