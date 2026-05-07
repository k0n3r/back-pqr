<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Repository;

use App\Bundles\pqr\Entity\PqrBalancer;
use App\Repository\BaseRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<PqrBalancer>
 */
class PqrBalancerRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PqrBalancer::class);
    }

    /**
     * @return PqrBalancer[]
     */
    public function findActive(): array
    {
        return $this->findBy(['active' => true]);
    }

    /**
     * @return PqrBalancer[]
     */
    public function findActiveBySysTipo(int $fkSysTipo): array
    {
        return $this->findBy(['fkSysTipo' => $fkSysTipo, 'active' => true]);
    }
}
