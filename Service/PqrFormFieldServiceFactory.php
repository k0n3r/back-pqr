<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Service;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;

final readonly class PqrFormFieldServiceFactory
{
    public function __construct(
        private EntityManagerInterface $em,
        private Connection $connection,
    ) {}

    public function create(int $id = 0): PqrFormFieldService
    {
        return new PqrFormFieldService($this->em, $this->connection, $id ?: null);
    }
}
