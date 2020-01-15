<?php

declare(strict_types=1);

namespace Saia\Pqr\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200103155146 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Creacion de tablas de pqr form field y pqr html field';
    }

    public function up(Schema $schema): void
    {

        if ($this->connection->getDatabasePlatform()->getName() == "mysql") {
            $this->platform->registerDoctrineTypeMapping('enum', 'string');
        }

        $table = $schema->createTable('pqr_form_fields');
        $table->addColumn('id', 'integer', [
            'autoincrement' => true
        ]);
        $table->setPrimaryKey(['id']);

        $table->addColumn('name', 'string', [
            'length' => 50
        ]);

        $table->addColumn('label', 'string', [
            'length' => 50
        ]);

        $table->addColumn('required', 'integer', [
            'length' => 1,
            'default' => 0
        ]);

        $table->addColumn('setting', 'text');

        $table->addColumn('fk_pqr_html_field', 'integer');

        $table->addColumn('fk_campos_formato', 'integer', [
            'default' => 0
        ]);

        $table->addColumn('order', 'integer', [
            'default' => 0
        ]);

        $table->addColumn('active', 'boolean', [
            'default' => 1
        ]);

        //-------------------------------------------

        $table2 = $schema->createTable('pqr_html_fields');
        $table2->addColumn('id', 'integer', [
            'autoincrement' => true
        ]);
        $table2->setPrimaryKey(['id']);

        $table2->addColumn('label', 'string', [
            'length' => 50
        ]);

        $table2->addColumn('type', 'string', [
            'length' => 50
        ]);

        $table2->addColumn('active', 'boolean', [
            'default' => 1
        ]);
    }

    public function down(Schema $schema): void
    {

        if ($this->connection->getDatabasePlatform()->getName() == "mysql") {
            $this->platform->registerDoctrineTypeMapping('enum', 'string');
        }

        $schema->dropTable('pqr_form_fields');
        $schema->dropTable('pqr_html_fields');
    }
}
