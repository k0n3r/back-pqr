<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Tests\Unit\Entity;

use App\Bundles\pqr\Entity\PqrBalancer;
use PHPUnit\Framework\TestCase;

final class PqrBalancerTest extends TestCase
{
    public function testDefaultActiveEsTrue(): void
    {
        self::assertTrue((new PqrBalancer())->isActive());
    }

    public function testSettersEncadenanYExponenValores(): void
    {
        $balancer = (new PqrBalancer())
            ->setFkCampoOpciones(-1)
            ->setFkSysTipo(8)
            ->setFkGrupo(3)
            ->setActive(false);

        self::assertSame(-1, $balancer->getFkCampoOpciones());
        self::assertSame(8, $balancer->getFkSysTipo());
        self::assertSame(3, $balancer->getFkGrupo());
        self::assertFalse($balancer->isActive());
    }
}
