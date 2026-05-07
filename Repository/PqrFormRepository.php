<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Repository;

use App\Bundles\pqr\Entity\PqrForm;
use App\Repository\BaseRepository;
use Doctrine\Persistence\ManagerRegistry;
use RuntimeException;

/**
 * @extends BaseRepository<PqrForm>
 */
class PqrFormRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PqrForm::class);
    }

    public function findActive(): ?PqrForm
    {
        return $this->findOneBy(['active' => true]);
    }

    public function findActiveOrFail(): PqrForm
    {
        $form = $this->findActive();
        if (!$form) {
            throw new RuntimeException('No active form was found');
        }
        return $form;
    }
}
