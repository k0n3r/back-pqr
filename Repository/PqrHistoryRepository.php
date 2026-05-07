<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Repository;

use App\Bundles\pqr\Entity\PqrHistory;
use App\Repository\BaseRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<PqrHistory>
 */
class PqrHistoryRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PqrHistory::class);
    }

    /**
     * @return PqrHistory[]
     */
    public function findByIdft(int $idft): array
    {
        return $this->findBy(['idft' => $idft], ['fecha' => 'ASC']);
    }
}
