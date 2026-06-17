<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Tests\Unit\Entity;

use App\Bundles\pqr\Entity\PqrResponseTime;
use PHPUnit\Framework\TestCase;

final class PqrResponseTimeTest extends TestCase
{
    public function testDefaultActiveEsTrue(): void
    {
        self::assertTrue((new PqrResponseTime())->isActive());
    }

    public function testSettersEncadenanYExponenValores(): void
    {
        $rt = (new PqrResponseTime())
            ->setFkCampoOpciones(-1)
            ->setFkSysTipo(12)
            ->setNumberDays(15)
            ->setActive(false);

        self::assertSame(-1, $rt->getFkCampoOpciones());
        self::assertSame(12, $rt->getFkSysTipo());
        self::assertSame(15, $rt->getNumberDays());
        self::assertFalse($rt->isActive());
    }
}
