<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Tests\Integration;

use App\Bundles\pqr\Repository\PqrLookupRepository;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Protege las consultas movidas al repositorio (Fase 3).
 *
 * Usa la conexión real del contenedor para construir el SQL (la QueryBuilder de
 * DBAL requiere la plataforma de la conexión), pero NO ejecuta consultas con
 * dependencia de datos: solo valida la forma del SQL y contratos de borde.
 */
final class PqrLookupRepositoryTest extends KernelTestCase
{
    private function repository(): PqrLookupRepository
    {
        self::bootKernel();
        $connection = self::getContainer()->get(Connection::class);

        return new PqrLookupRepository($connection);
    }

    public function testBuildSearchByNumberQueryArmaElSqlEsperado(): void
    {
        $qb = $this->repository()->buildSearchByNumberQuery('123');

        $sql = $qb->getSQL();
        self::assertStringContainsString('ft_pqr', $sql);
        self::assertStringContainsString('documento', $sql);
        self::assertStringContainsString('d.numero = :numero', $sql);
        self::assertSame('123', $qb->getParameter('numero'));
    }

    public function testFindCampoOpcionesByIdsConArregloVacioRetornaVacio(): void
    {
        self::assertSame([], $this->repository()->findCampoOpcionesByIds([]));
    }

    public function testFindActiveDependenciesRetornaCatalogoConFormaEsperada(): void
    {
        $rows = $this->repository()->findActiveDependencies(null);

        self::assertIsArray($rows);
        // El catálogo puede estar vacío en el clon de test; si trae filas,
        // deben exponer las claves id y nombre.
        if ($rows !== []) {
            self::assertArrayHasKey('id', $rows[0]);
            self::assertArrayHasKey('nombre', $rows[0]);
        }
    }
}
