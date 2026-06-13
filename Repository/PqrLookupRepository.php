<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Repository;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use RuntimeException;
use Saia\models\documento\Documento;
use Throwable;

/**
 * Repositorio DBAL para consultas sobre documento + ft_pqr y catálogos PQR.
 *
 * Centraliza queries que antes vivían en controladores y servicios del bundle.
 * Mayormente lectura; la escritura sobre PQR pasa por la capa legacy (FtPqr).
 */
final readonly class PqrLookupRepository
{
    private const int CATALOG_LIMIT = 40;

    public function __construct(
        private Connection $connection,
    ) {
    }

    /**
     * Construye el QueryBuilder para buscar radicados PQR por número
     * (excluye documentos eliminados). El controlador lo entrega a
     * FtPqr::findByQueryBuilder() para hidratar los modelos legacy.
     */
    public function buildSearchByNumberQuery(string $numero): QueryBuilder
    {
        return $this->connection
            ->createQueryBuilder()
            ->select('ft.*')
            ->from('ft_pqr', 'ft')
            ->join('ft', 'documento', 'd', 'ft.documento_iddocumento=d.iddocumento')
            ->where('d.estado<>:estado')
            ->setParameter('estado', Documento::ELIMINADO)
            ->andWhere('d.numero = :numero')
            ->setParameter('numero', $numero, ParameterType::INTEGER);
    }

    /**
     * Catálogo de dependencias activas para autocompletar.
     *
     * @return list<array{id: int, nombre: string}>
     */
    public function findActiveDependencies(?string $term): array
    {
        return $this->catalogQuery('dependencia', 'iddependencia', $term)
            ->executeQuery()
            ->fetchAllAssociative();
    }

    /**
     * Catálogo de países activos para autocompletar.
     *
     * @return list<array{id: int, nombre: string}>
     */
    public function findActiveCountries(?string $term): array
    {
        return $this->catalogQuery('pais', 'idpais', $term)
            ->executeQuery()
            ->fetchAllAssociative();
    }

    /**
     * Catálogo de departamentos activos, opcionalmente filtrado por país.
     *
     * @return list<array{id: int, nombre: string}>
     */
    public function findActiveDepartments(?string $term, ?int $countryId): array
    {
        $qb = $this->catalogQuery('departamento', 'iddepartamento', $term);

        if ($countryId) {
            $qb->andWhere('pais_idpais=:pais')->setParameter('pais', $countryId, ParameterType::INTEGER);
        }

        return $qb->executeQuery()->fetchAllAssociative();
    }

    /**
     * Dependencias para el campo tipo "dependencia" del formulario.
     * Si $allowedIds es null no se restringe; si es un arreglo, solo esas.
     *
     * @param int[]|null $allowedIds
     * @return list<array{id: int, text: string}>
     */
    public function findDependenciesForField(?int $id, ?string $term, ?array $allowedIds): array
    {
        $qb = $this->connection
            ->createQueryBuilder()
            ->select('iddependencia as id,nombre as text')
            ->from('dependencia');

        if ($id) {
            $qb->where('iddependencia=:iddependencia')
                ->setParameter('iddependencia', $id, ParameterType::INTEGER);

            return $qb->executeQuery()->fetchAllAssociative();
        }

        $qb->where('estado=1')
            ->orderBy('nombre', 'ASC')
            ->setFirstResult(0)
            ->setMaxResults(self::CATALOG_LIMIT);

        if ($term !== null) {
            $qb->andWhere('nombre like :nombre')
                ->setParameter('nombre', $term !== '' ? '%'.$term.'%' : $term);
        }

        if ($allowedIds !== null) {
            $qb->andWhere('iddependencia in (:ids)')
                ->setParameter('ids', $allowedIds, ArrayParameterType::INTEGER);
        }

        return $qb->executeQuery()->fetchAllAssociative();
    }

    /**
     * Localidades (municipio + departamento + país) para el campo "localidad".
     * Si $countryId es null no se filtra por país.
     *
     * @return list<array{id: int, text: string}>
     */
    public function findLocalitiesForField(?int $id, ?string $term, ?int $countryId): array
    {
        $qb = $this->connection
            ->createQueryBuilder()
            ->select(
                "CONCAT(a.nombre,
            CONCAT(
                ' - ',
                CONCAT(
                    b.nombre,
                    CONCAT(
                        ' - ',
                        c.nombre
                    )
                )
            )
        ) AS text",
                'a.idmunicipio as id',
            )
            ->from('municipio', 'a')
            ->join('a', 'departamento', 'b', 'a.departamento_iddepartamento = b.iddepartamento')
            ->join('b', 'pais', 'c', 'b.pais_idpais = c.idpais');

        if ($id) {
            $qb->andWhere('idmunicipio=:idmunicipio')
                ->setParameter('idmunicipio', $id, ParameterType::INTEGER);

            return $qb->executeQuery()->fetchAllAssociative();
        }

        $qb->where("CONCAT(a.nombre,CONCAT(' ',b.nombre)) like :query")
            ->andWhere('a.estado = 1 AND b.estado = 1 AND c.estado = 1')
            ->setParameter('query', '%'.((string)$term).'%')
            ->orderBy('a.nombre', 'ASC')
            ->setFirstResult(0)
            ->setMaxResults(self::CATALOG_LIMIT);

        if ($countryId) {
            $qb->andWhere('c.idpais=:idpais')->setParameter('idpais', $countryId, ParameterType::INTEGER);
        }

        return $qb->executeQuery()->fetchAllAssociative();
    }

    /**
     * Carga en una sola consulta las opciones de campo (campo_opciones)
     * por sus identificadores, indexadas por idcampo_opciones.
     * Evita el patrón N+1 de instanciar CampoOpciones dentro de un bucle.
     *
     * @param int[] $ids
     * @return array<int, array{orden: int, valor: string}>
     */
    public function findCampoOpcionesByIds(array $ids): array
    {
        if (!$ids) {
            return [];
        }

        $rows = $this->connection
            ->createQueryBuilder()
            ->select('idcampo_opciones', 'orden', 'valor')
            ->from('campo_opciones')
            ->where('idcampo_opciones in (:ids)')
            ->setParameter('ids', $ids, ArrayParameterType::INTEGER)
            ->executeQuery()
            ->fetchAllAssociative();

        $map = [];
        foreach ($rows as $row) {
            $map[(int)$row['idcampo_opciones']] = [
                'orden' => (int)$row['orden'],
                'valor' => (string)$row['valor'],
            ];
        }

        return $map;
    }

    /**
     * Base común de los catálogos: tabla con columnas `estado` y `nombre`.
     * $table e $idColumn son valores internos controlados (no entrada de usuario).
     */
    private function catalogQuery(string $table, string $idColumn, ?string $term): QueryBuilder
    {
        $qb = $this->connection
            ->createQueryBuilder()
            ->select("$idColumn as id,nombre")
            ->from($table)
            ->where('estado=1')
            ->orderBy('nombre', 'ASC')
            ->setFirstResult(0)
            ->setMaxResults(self::CATALOG_LIMIT);

        if ($term) {
            $qb->andWhere('nombre like :nombre')->setParameter('nombre', '%'.$term.'%');
        }

        return $qb;
    }

    /**
     * Recupera un radicado PQR por número, opcionalmente filtrando por
     * email contenido en la descripción del documento (validación de acceso).
     *
     * @return array{iddocumento: int, number: string, state: string, filed_at: ?string, response_deadline: ?string, pqr_id: int}|null
     */
    public function findFilingByNumberAndEmail(string $filingNumber, string $email): ?array
    {
        $emailLower = strtolower(trim($email));

        try {
            $row = $this->connection->fetchAssociative(
                <<<'SQL'
                SELECT
                    d.iddocumento                                     AS iddocumento,
                    d.numero                                          AS number,
                    COALESCE(d.estado_aprobacion, d.estado,
                             'desconocido')                           AS state,
                    d.fecha                                           AS filed_at,
                    d.fecha_limite                                    AS response_deadline,
                    ft.idft_pqr                                       AS pqr_id
                FROM documento d
                INNER JOIN ft_pqr ft ON ft.fk_documento = d.iddocumento
                WHERE d.numero = :number
                  AND (:email = '' OR LOWER(d.descripcion) LIKE :emailLike)
                LIMIT 1
                SQL,
                [
                    'number'    => $filingNumber,
                    'email'     => $emailLower,
                    'emailLike' => '%' . $emailLower . '%',
                ],
            );
        } catch (Throwable $e) {
            throw new RuntimeException('Cannot query PQR filing.', previous: $e);
        }

        if ($row === false) {
            return null;
        }

        return [
            'iddocumento'       => (int) $row['iddocumento'],
            'number'            => (string) $row['number'],
            'state'             => (string) $row['state'],
            'filed_at'          => $row['filed_at'] !== null ? (string) $row['filed_at'] : null,
            'response_deadline' => $row['response_deadline'] !== null ? (string) $row['response_deadline'] : null,
            'pqr_id'            => (int) $row['pqr_id'],
        ];
    }
}
