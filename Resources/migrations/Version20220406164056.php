<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Resources\migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220406164056 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Se actualiza el campo fecha formato_fecha_radicado';
    }

    public function up(Schema $schema): void
    {
        $sql = "UPDATE formato SET formato_fecha_radicado='Ymd' WHERE nombre LIKE 'pqr' AND (formato_fecha_radicado IS NULL OR formato_fecha_radicado='')";
        $this->connection->executeQuery($sql);
    }

    public function down(Schema $schema): void
    {
        $sql = "UPDATE formato SET formato_fecha_radicado=NULL WHERE nombre LIKE 'pqr' AND formato_fecha_radicado='Ymd'";
        $this->connection->executeQuery($sql);
    }
}
