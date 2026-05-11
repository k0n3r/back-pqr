<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Service;

use App\Bundles\pqr\Services\PqrFormFieldService;
use Doctrine\ORM\EntityManagerInterface;

final class PqrFormFieldServiceFactory
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function create(int $id = 0): PqrFormFieldService
    {
        return new PqrFormFieldService($this->em, $id ?: null);
    }
}
