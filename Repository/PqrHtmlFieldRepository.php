<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Repository;

use App\Bundles\pqr\Entity\PqrHtmlField;
use App\Repository\BaseRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<PqrHtmlField>
 */
class PqrHtmlFieldRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PqrHtmlField::class);
    }
}
