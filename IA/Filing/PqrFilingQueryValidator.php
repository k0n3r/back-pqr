<?php

declare(strict_types=1);

namespace App\Bundles\pqr\IA\Filing;

use App\Bundles\ia\Services\Filing\FilingQueryValidatorInterface;
use App\Bundles\pqr\Services\models\PqrForm;
use Doctrine\DBAL\Connection;

/**
 * Valida que el email aportado coincida con el registrado en el PQR.
 *
 * El email del ciudadano queda almacenado en el campo descripcion del
 * documento (generado por el formulario PQR). Se busca de forma
 * case-insensitive para evitar falsos negativos por mayúsculas.
 */
class PqrFilingQueryValidator implements FilingQueryValidatorInterface
{
    public function getFormatId(): int
    {
        return (int) PqrForm::getInstance()->fk_formato;
    }

    public function validate(int $documentId, array $fields, Connection $connection): bool
    {
        $email = strtolower(trim($fields['email'] ?? ''));
        if ($email === '') {
            return false;
        }

        $row = $connection->fetchAssociative(
            'SELECT descripcion FROM documento WHERE iddocumento = :id LIMIT 1',
            ['id' => $documentId],
        );

        if ($row === false || !isset($row['descripcion'])) {
            return false;
        }

        return str_contains(strtolower((string) $row['descripcion']), $email);
    }
}
