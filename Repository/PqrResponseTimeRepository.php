<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Repository;

use App\Bundles\pqr\Entity\PqrResponseTime;
use App\Repository\BaseRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<PqrResponseTime>
 */
class PqrResponseTimeRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PqrResponseTime::class);
    }

    /**
     * @return PqrResponseTime[]
     */
    public function findActive(): array
    {
        return $this->findBy(['active' => true]);
    }

    public function findActiveBySysTipo(int $fkSysTipo): ?PqrResponseTime
    {
        return $this->findOneBy(['fkSysTipo' => $fkSysTipo, 'active' => true]);
    }
}
