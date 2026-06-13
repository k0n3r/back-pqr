<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Tests\Unit\Entity;

use App\Bundles\pqr\Entity\PqrHistory;
use App\Entity\Funcionario;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

/**
 * Protege la conversión de fk_funcionario a relación Doctrine ManyToOne (Fase 2).
 *
 * getFkFuncionario() debe delegar en la asociación (funcionario->getId()),
 * manteniendo la compatibilidad con los consumidores legacy que aún piden el int.
 */
final class PqrHistoryTest extends TestCase
{
    public function testGetFkFuncionarioDelegaEnLaRelacion(): void
    {
        $funcionario = $this->createMock(Funcionario::class);
        $funcionario->method('getId')->willReturn(42);

        $history = (new PqrHistory())->setFuncionario($funcionario);

        self::assertSame($funcionario, $history->getFuncionario());
        self::assertSame(42, $history->getFkFuncionario());
    }

    public function testSettersEncadenanYExponenLosValores(): void
    {
        $fecha   = new DateTimeImmutable('2026-06-12 10:00:00');
        $history = (new PqrHistory())
            ->setIdft(7)
            ->setTipo(PqrHistory::TIPO_RESPUESTA)
            ->setIdfk(3)
            ->setDescripcion('respuesta enviada')
            ->setFecha($fecha);

        self::assertSame(7, $history->getIdft());
        self::assertSame(PqrHistory::TIPO_RESPUESTA, $history->getTipo());
        self::assertSame(3, $history->getIdfk());
        self::assertSame('respuesta enviada', $history->getDescripcion());
        self::assertSame($fecha, $history->getFecha());
    }
}
