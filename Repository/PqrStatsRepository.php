<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Repository;

use Doctrine\DBAL\Connection;

/**
 * Repositorio DBAL para queries de estadísticas y catálogos PQR usadas
 * por las herramientas del agente IA (PqrStatsTool).
 *
 * Toda la lógica SQL vive aquí; el servicio solo orquesta y formatea.
 */
final readonly class PqrStatsRepository
{
    private const string VIEW = 'vpqr';

    public function __construct(
        private Connection $connection,
    ) {
    }

    /**
     * Total con filtros parametrizados y nombre de tabla fijo.
     *
     * @param array<string, scalar> $params
     */
    public function countTotal(string $whereSql, array $params): int
    {
        $sql = sprintf('SELECT COUNT(*) FROM %s %s', self::VIEW, $whereSql);

        return (int) $this->connection->fetchOne($sql, $params);
    }

    /**
     * Agrupación por columna validada por el llamante.
     *
     * @param array<string, scalar> $params
     * @return list<array<string, mixed>>
     */
    public function countGrouped(string $groupBy, string $whereSql, array $params, int $limit): array
    {
        $sql = sprintf(
            'SELECT %s, COUNT(*) as total FROM %s %s GROUP BY %s ORDER BY total DESC LIMIT %d',
            $groupBy,
            self::VIEW,
            $whereSql,
            $groupBy,
            $limit,
        );

        return $this->connection->fetchAllAssociative($sql, $params);
    }

    /**
     * @return list<array{id: int, etiqueta: string}>
     */
    public function listActiveDependencias(): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT iddependencia AS id, nombre AS etiqueta FROM dependencia WHERE estado = 1 ORDER BY nombre',
        );

        return array_map(
            static fn (array $row): array => [
                'id'       => (int) $row['id'],
                'etiqueta' => (string) $row['etiqueta'],
            ],
            $rows,
        );
    }

    /**
     * @return list<array{id: int, etiqueta: string}>
     */
    public function listPqrTypes(string $formatName, string $fieldName): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT co.idcampo_opciones AS id, co.valor AS etiqueta
               FROM campo_opciones co
               JOIN campos_formato cf ON cf.idcampos_formato = co.fk_campos_formato
               JOIN formato f         ON f.idformato         = cf.formato_idformato
              WHERE f.nombre = :format
                AND cf.nombre = :campo
              ORDER BY co.llave',
            ['format' => $formatName, 'campo' => $fieldName],
        );

        return array_map(
            static fn (array $row): array => [
                'id'       => (int) $row['id'],
                'etiqueta' => (string) $row['etiqueta'],
            ],
            $rows,
        );
    }
}
