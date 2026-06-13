<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Service;

use App\Bundles\pqr\Repository\PqrLookupRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class PqrFormFieldServiceFactory
{
    public function __construct(
        private EntityManagerInterface $em,
        private Connection $connection,
        private EventDispatcherInterface $eventDispatcher,
        private TranslatorInterface $translator,
        private PqrLookupRepository $pqrLookupRepository,
    ) {
    }

    public function create(int $id = 0): PqrFormFieldService
    {
        return new PqrFormFieldService(
            $this->em,
            $this->connection,
            $this->eventDispatcher,
            $this->translator,
            $this->pqrLookupRepository,
            $id ?: null,
        );
    }
}
