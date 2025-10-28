<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Resources\migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251028205717 extends AbstractMigration
{
    //TODO: Se puede borrar
    public function getDescription(): string
    {
        return 'Se crea campo para contar dias corrido';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable("pqr_forms");
        if (!$table->hasColumn('enable_con_days')) {
            $table->addColumn('enable_con_days', 'boolean', ['default' => 0]);
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
