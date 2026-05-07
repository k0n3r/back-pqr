<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Repository;

use App\Bundles\pqr\Entity\PqrNotification;
use App\Repository\BaseRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<PqrNotification>
 */
class PqrNotificationRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PqrNotification::class);
    }

    /**
     * @return PqrNotification[]
     */
    public function findByPqrForm(int $pqrFormId): array
    {
        return $this->findBy(['fkPqrForm' => $pqrFormId]);
    }
}
