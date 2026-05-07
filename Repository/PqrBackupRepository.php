<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Repository;

use App\Bundles\pqr\Entity\PqrBackup;
use App\Repository\BaseRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<PqrBackup>
 */
class PqrBackupRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PqrBackup::class);
    }

    public function findByDocumento(int $idDocumento): ?PqrBackup
    {
        return $this->findOneBy(['fkDocumento' => $idDocumento]);
    }

    /**
     * @return PqrBackup[]
     */
    public function findByPqr(int $idPqr): array
    {
        return $this->findBy(['fkPqr' => $idPqr]);
    }
}
