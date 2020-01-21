<?php

declare(strict_types=1);

namespace Saia\Pqr\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200115001556 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Se crea la tabla Pqr Form';
    }

    public function up(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform()->getName() == "mysql") {
            $this->platform->registerDoctrineTypeMapping('enum', 'string');
        }

        $table = $schema->createTable('pqr_forms');
        $table->addColumn('id', 'integer', [
            'autoincrement' => true
        ]);
        $table->setPrimaryKey(['id']);

        $table->addColumn('fk_formato', 'integer');
        $table->addColumn('fk_contador', 'integer');
        $table->addColumn('label', 'string');

        $table->addColumn('active', 'boolean', [
            'default' => 1
        ]);

        $table2 = $schema->getTable('pqr_form_fields');
        $table2->addColumn('fk_pqr_form', 'integer');
    }

    public function down(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform()->getName() == "mysql") {
            $this->platform->registerDoctrineTypeMapping('enum', 'string');
        }

        $schema->dropTable('pqr_forms');

        $table2 = $schema->getTable('pqr_form_fields');
        $table2->dropColumn('fk_pqr_form');
    }
}
