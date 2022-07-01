<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Resources\migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210714002400 extends AbstractMigration
{
    public function getDescription(): string
    {
        //TODO: Se puede borrar
        return 'Se crea campo tipo fecha';
    }

    public function up(Schema $schema): void
    {
        $sql = "SELECT id FROM pqr_html_fields WHERE type LIKE 'date'";
        $exist = (int)$this->connection->fetchOne($sql);
        if (!$exist) {
            $this->connection->insert('pqr_html_fields', [
                'label'     => 'Fecha',
                'type'      => 'date',
                'type_saia' => 'Date',
                'uniq'      => 0,
                'active'    => 1
            ]);
        }
    }

    public function down(Schema $schema): void
    {
        $this->connection->delete('pqr_html_fields', [
            'type' => 'date',
        ]);
    }
}
