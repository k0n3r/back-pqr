<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Resources\migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230925163207 extends AbstractMigration
{
    public function getDescription(): string
    {
        //TODO: Se puede borrar
        return 'Se actualiza el formato_fecha_radicado de la respuesta';
    }

    public function up(Schema $schema): void
    {
        $this->connection->update('formato', [
            'formato_fecha_radicado' => 'Ymd'
        ], [
            'nombre' => 'pqr_respuesta'
        ]);
    }

    public function down(Schema $schema): void
    {
        $this->connection->update('formato', [
            'formato_fecha_radicado' => null
        ], [
            'nombre' => 'pqr_respuesta'
        ]);
    }
}
