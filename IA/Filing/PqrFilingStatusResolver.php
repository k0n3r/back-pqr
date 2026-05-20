<?php

declare(strict_types=1);

namespace App\Bundles\pqr\IA\Filing;

use App\Bundles\ia\Services\Filing\FilingStatusResolverInterface;
use App\Bundles\pqr\Services\models\PqrForm;
use Doctrine\DBAL\Connection;

/**
 * Resolver de estado de radicados para el formato PQR.
 *
 * Valida que el email aportado por el usuario aparezca en el campo
 * descripcion del documento antes de retornar el resultado — evita
 * que un ciudadano consulte PQRs ajenas con solo el número.
 *
 * La query puede extenderse para hacer JOIN con tablas propias del módulo
 * PQR (pqr_solicitud, etc.) si se necesitan campos adicionales.
 */
class PqrFilingStatusResolver implements FilingStatusResolverInterface
{
    public function getFormatId(): int
    {
        return (int) PqrForm::getInstance()->fk_formato;
    }

    public function resolve(string $filingNumber, array $extraFields, Connection $connection): ?array
    {
        $email = strtolower(trim($extraFields['email'] ?? ''));

        if ($email === '') {
            return null;
        }

        $row = $connection->fetchAssociative(
            <<<'SQL'
            SELECT
                d.numero                                          AS number,
                COALESCE(d.estado_aprobacion, d.estado,
                         'desconocido')                           AS state,
                d.fecha                                           AS filed_at,
                d.fecha_limite                                    AS response_deadline
            FROM documento d
            WHERE d.numero = :number
              AND LOWER(d.descripcion) LIKE :email
            LIMIT 1
            SQL,
            [
                'number' => $filingNumber,
                'email'  => '%' . $email . '%',
            ],
        );

        return $row !== false ? $row : null;
    }
}
