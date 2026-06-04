<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Service;

use App\Bundles\pqr\Entity\PqrForm;
use App\Bundles\pqr\Entity\PqrFormField;
use App\Bundles\pqr\Repository\PqrFormFieldRepository;
use App\Bundles\pqr\Repository\PqrFormRepository;
use App\Bundles\pqr\Repository\PqrNotificationRepository;

/**
 * Reemplaza el patrón singleton legacy `PqrForm::getInstance()`.
 * El formulario activo se cachea por request.
 */
class PqrFormProvider
{
    private ?PqrForm $cached = null;

    public function __construct(
        private readonly PqrFormRepository $pqrFormRepository,
        private readonly PqrFormFieldRepository $pqrFormFieldRepository,
        private readonly PqrNotificationRepository $pqrNotificationRepository,
    ) {}

    public function get(): PqrForm
    {
        return $this->cached ??= $this->pqrFormRepository->findActiveOrFail();
    }

    public function getOrNull(): ?PqrForm
    {
        return $this->cached ??= $this->pqrFormRepository->findActive();
    }

    public function refresh(): void
    {
        $this->cached = null;
    }

    public function getFields(): array
    {
        return $this->pqrFormFieldRepository->findByPqrFormOrdered($this->get()->getId());
    }

    public function getNotifications(): array
    {
        return $this->pqrNotificationRepository->findByPqrForm($this->get()->getId());
    }

    public function getFieldByName(string $name): ?PqrFormField
    {
        foreach ($this->getFields() as $field) {
            if ($field->getName() === $name) {
                return $field;
            }
        }

        return null;
    }

    public function countFields(): int
    {
        return count($this->getFields());
    }
}
