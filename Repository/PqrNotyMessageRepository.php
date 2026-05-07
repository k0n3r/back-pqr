<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Repository;

use App\Bundles\pqr\Entity\PqrNotyMessage;
use App\Repository\BaseRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<PqrNotyMessage>
 */
class PqrNotyMessageRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PqrNotyMessage::class);
    }

    public function findByName(string $name): ?PqrNotyMessage
    {
        return $this->findOneBy(['name' => $name]);
    }
}
