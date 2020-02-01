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
        $this->tablePqrFormFields($table);

        $table2 = $schema->createTable('pqr_html_fields');
        $this->tablePqrHtmlFields($table2);

        $table3 = $schema->createTable('pqr_forms');
        $this->tablePqrForm($table3);
    }


    public function tablePqrFormFields($table)
    {

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

        $table->addColumn('fk_pqr_form', 'integer');

        $table->addColumn('fk_campos_formato', 'integer', [
            'default' => 0,
            'notnull' => false
        ]);

        $table->addColumn('order', 'integer', [
            'default' => 0,
            'notnull' => false
        ]);

        $table->addColumn('active', 'boolean', [
            'default' => 1,
            'notnull' => false
        ]);
    }

    public function tablePqrHtmlFields($table)
    {
        $table->addColumn('id', 'integer', [
            'autoincrement' => true
        ]);
        $table->setPrimaryKey(['id']);

        $table->addColumn('label', 'string', [
            'length' => 50
        ]);

        $table->addColumn('type', 'string', [
            'length' => 50
        ]);

        $table->addColumn('active', 'boolean', [
            'default' => 1
        ]);
    }

    public function tablePqrForm($table)
    {
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
    }

    public function down(Schema $schema): void
    {

        if ($this->connection->getDatabasePlatform()->getName() == "mysql") {
            $this->platform->registerDoctrineTypeMapping('enum', 'string');
        }

        $schema->dropTable('pqr_form_fields');
        $schema->dropTable('pqr_html_fields');
        $schema->dropTable('pqr_forms');
    }
}
