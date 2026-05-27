<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Repository;

use Doctrine\DBAL\Connection;
use RuntimeException;
use Throwable;

/**
 * Repositorio DBAL para consultas sobre documento + ft_pqr.
 *
 * Centraliza queries que antes vivían en servicios del bundle PQR/IA.
 * Solo lectura — la escritura sobre PQR pasa por la capa legacy (FtPqr).
 */
final readonly class PqrLookupRepository
{
    public function __construct(
        private Connection $connection,
    ) {
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
