<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Tests\Unit\Entity;

use App\Bundles\pqr\Entity\PqrBackup;
use PHPUnit\Framework\TestCase;

final class PqrBackupTest extends TestCase
{
    public function testDefaultDataJsonEsArregloVacio(): void
    {
        self::assertSame([], (new PqrBackup())->getDataJson());
    }

    public function testSettersEncadenanYExponenValores(): void
    {
        $backup = (new PqrBackup())
            ->setFkDocumento(100)
            ->setFkPqr(200)
            ->setDataJson(['campo' => 'valor']);

        self::assertSame(100, $backup->getFkDocumento());
        self::assertSame(200, $backup->getFkPqr());
        self::assertSame(['campo' => 'valor'], $backup->getDataJson());
    }
}
