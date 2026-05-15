<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Service;

use App\Bundles\pqr\Entity\PqrNotification;
use App\Bundles\pqr\Repository\PqrNotificationRepository;
use App\Bundles\pqr\Service\PqrFormProvider;

readonly class PqrNotificationService
{
    public function __construct(
        private PqrNotificationRepository $repository,
        private PqrFormProvider $pqrFormProvider,
    ) {
    }

    public function getRepository(): PqrNotificationRepository
    {
        return $this->repository;
    }

    public function create(array $attributes): PqrNotification
    {
        $entity = new PqrNotification();
        $entity->setFkPqrForm($attributes['fk_pqr_form'] ?? $this->pqrFormProvider->get()->getId());
        $entity->setFkFuncionario((int)$attributes['fk_funcionario']);
        $entity->setEmail((bool)($attributes['email'] ?? 0));
        $entity->setNotify((bool)($attributes['notify'] ?? 1));
        $this->repository->create($entity);

        return $entity;
    }

    public function update(PqrNotification $entity, array $attributes): void
    {
        if (array_key_exists('fk_funcionario', $attributes)) {
            $entity->setFkFuncionario((int)$attributes['fk_funcionario']);
        }
        if (array_key_exists('email', $attributes)) {
            $entity->setEmail((bool)$attributes['email']);
        }
        if (array_key_exists('notify', $attributes)) {
            $entity->setNotify((bool)$attributes['notify']);
        }
        if (array_key_exists('fk_pqr_form', $attributes)) {
            $entity->setFkPqrForm((int)$attributes['fk_pqr_form']);
        }
        $this->repository->update();
    }

    public function delete(PqrNotification $entity): void
    {
        $this->repository->delete($entity);
        $this->repository->flush();
    }

    public function toArray(PqrNotification $entity): array
    {
        return [
            'id'             => $entity->getId(),
            'fk_funcionario' => $entity->getFkFuncionario(),
            'fk_pqr_form'    => $entity->getFkPqrForm(),
            'email'          => $entity->isEmail() ? 1 : 0,
            'notify'         => $entity->isNotify() ? 1 : 0,
        ];
    }
}
