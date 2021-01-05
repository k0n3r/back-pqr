<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Resources\migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201012222241 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Se crea el campo Categoria de tipos';
    }

    public function up(Schema $schema): void
    {
        $name = 'Categoria de tipos';
        $sql = "SELECT id FROM pqr_html_fields WHERE label LIKE '{$name}'";
        if (!$this->connection->fetchOne($sql)) {
            $this->connection->insert('pqr_html_fields', [
                'label' => $name,
                'type' => 'subTypesPqr',
                'type_saia' => 'Select',
                'active' => 1,
                'uniq' => 1
            ]);
        }
    }

    public function down(Schema $schema): void
    {
    }
}
