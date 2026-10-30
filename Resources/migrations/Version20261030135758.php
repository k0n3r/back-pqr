<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Resources\migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Actualiza el catálogo pqr_html_fields: el campo type=email ahora apunta al
 * componente de núcleo Email (etiqueta_html='Email', ver
 * migrations/Version20261021110315.php), que reemplaza a Text+opciones.type=email.
 * Esta actualización vivía originalmente en esa migración del núcleo —
 * se mueve aquí porque pqr_html_fields es una tabla propia del bundle PQR.
 */
final class Version20261030135758 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'pqr_html_fields: type=email apunta al componente de núcleo Email';
    }

    public function up(Schema $schema): void
    {
        $this->connection->update('pqr_html_fields', [
            'type_saia' => 'Email',
        ], [
            'type' => 'email',
        ]);
    }

    public function down(Schema $schema): void
    {
        $this->connection->update('pqr_html_fields', [
            'type_saia' => 'Text',
        ], [
            'type' => 'email',
        ]);
    }
}
