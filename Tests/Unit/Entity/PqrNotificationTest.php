<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Tests\Unit\Entity;

use App\Bundles\pqr\Entity\PqrNotification;
use App\Entity\Funcionario;
use PHPUnit\Framework\TestCase;

final class PqrNotificationTest extends TestCase
{
    public function testDefaults(): void
    {
        $notification = new PqrNotification();
        self::assertFalse($notification->isEmail());
        self::assertFalse($notification->isNotify());
    }

    public function testGetFkFuncionarioDelegaEnLaRelacion(): void
    {
        $funcionario = $this->createMock(Funcionario::class);
        $funcionario->method('getId')->willReturn(99);

        $notification = (new PqrNotification())->setFuncionario($funcionario);

        self::assertSame($funcionario, $notification->getFuncionario());
        self::assertSame(99, $notification->getFkFuncionario());
    }

    public function testSettersEncadenan(): void
    {
        $notification = (new PqrNotification())
            ->setFkPqrForm(4)
            ->setEmail(true)
            ->setNotify(true);

        self::assertSame(4, $notification->getFkPqrForm());
        self::assertTrue($notification->isEmail());
        self::assertTrue($notification->isNotify());
    }
}
