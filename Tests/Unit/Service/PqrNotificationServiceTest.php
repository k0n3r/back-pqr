<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Tests\Unit\Service;

use App\Bundles\pqr\Entity\PqrNotification;
use App\Bundles\pqr\Repository\PqrNotificationRepository;
use App\Bundles\pqr\Service\PqrFormProvider;
use App\Bundles\pqr\Service\PqrNotificationService;
use App\Entity\Funcionario;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class PqrNotificationServiceTest extends TestCase
{
    public function testToArrayMapeaLaEntidadConElFuncionarioExpandido(): void
    {
        $service = new PqrNotificationService(
            $this->createMock(PqrNotificationRepository::class),
            $this->createMock(PqrFormProvider::class),
            $this->createMock(EntityManagerInterface::class),
        );

        $funcionario = $this->createMock(Funcionario::class);
        $funcionario->method('getNombres')->willReturn('Juan Pérez');

        $entity = $this->createMock(PqrNotification::class);
        $entity->method('getId')->willReturn(5);
        $entity->method('getFkFuncionario')->willReturn(99);
        $entity->method('getFuncionario')->willReturn($funcionario);
        $entity->method('getFkPqrForm')->willReturn(4);
        $entity->method('isEmail')->willReturn(true);
        $entity->method('isNotify')->willReturn(false);

        self::assertSame([
            'id'             => 5,
            'fk_funcionario' => [
                'id'   => 99,
                'text' => 'Juan Pérez',
            ],
            'fk_pqr_form'    => 4,
            'email'          => 1,
            'notify'         => 0,
        ], $service->toArray($entity));
    }
}
