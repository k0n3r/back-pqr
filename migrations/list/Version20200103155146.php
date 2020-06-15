<?php

declare(strict_types=1);

namespace Saia\Pqr\migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
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

        $table = $schema->createTable('pqr_form_fields');
        $this->tablePqrFormFields($table);

        $table2 = $schema->createTable('pqr_html_fields');
        $this->tablePqrHtmlFields($table2);

        $table3 = $schema->createTable('pqr_forms');
        $this->tablePqrForm($table3);

        $table4 = $schema->createTable('pqr_backups');
        $this->tablePqrBackup($table4);

        $table5 = $schema->createTable('pqr_response_templates');
        $this->tablePqrResponseTemplate($table5);

        $table6 = $schema->createTable('pqr_notifications');
        $this->tablePqrNotify($table6);

        $table7 = $schema->createTable('pqr_history');
        $this->tablePqrHistory($table7);
    }

    public function tablePqrFormFields(Table $table)
    {

        $table->addColumn('id', 'integer', [
            'autoincrement' => true
        ]);
        $table->setPrimaryKey(['id']);

        $table->addColumn('name', 'string', [
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_general_ci',
            'length' => 50
        ]);

        $table->addColumn('label', 'string', [
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_general_ci',
            'length' => 50
        ]);

        $table->addColumn('required', 'boolean', [
            'default' => 0,
            'notnull' => false
        ]);

        $table->addColumn('anonymous', 'boolean', [
            'default' => 0,
            'notnull' => false
        ]);

        $table->addColumn('show_report', 'boolean', [
            'default' => 0,
            'notnull' => false
        ]);

        $table->addColumn('required_anonymous', 'boolean', [
            'default' => 0,
            'notnull' => false
        ]);

        $table->addColumn('setting', 'text', [
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_general_ci'
        ]);

        $table->addColumn('fk_pqr_html_field', 'integer');
        $table->addIndex(['fk_pqr_html_field'], 'i_fk_pqr_html_field');

        $table->addColumn('fk_pqr_form', 'integer');
        $table->addIndex(['fk_pqr_form'], 'i_fk_pqr_form');

        $table->addColumn('fk_campos_formato', 'integer', [
            'default' => 0,
            'notnull' => false
        ]);
        $table->addIndex(['fk_campos_formato'], 'i_fk_campos_formato');


        $table->addColumn('system', 'boolean', [
            'default' => 0,
            'notnull' => false
        ]);

        $table->addColumn('orden', 'integer', [
            'default' => 0,
            'notnull' => false
        ]);

        $table->addColumn('active', 'boolean', [
            'default' => 1,
            'notnull' => false
        ]);
    }

    public function tablePqrHtmlFields(Table $table)
    {
        $table->addColumn('id', 'integer', [
            'autoincrement' => true
        ]);
        $table->setPrimaryKey(['id']);

        $table->addColumn('label', 'string', [
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_general_ci',
            'length' => 50
        ]);

        $table->addColumn('type', 'string', [
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_general_ci',
            'length' => 50
        ]);

        $table->addColumn('type_saia', 'string', [
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_general_ci',
            'length' => 50
        ]);

        $table->addColumn('uniq', 'boolean', [
            'default' => false
        ]);

        $table->addColumn('active', 'boolean', [
            'default' => 1
        ]);
    }

    public function tablePqrForm(Table $table)
    {
        $table->addColumn('id', 'integer', [
            'autoincrement' => true
        ]);
        $table->setPrimaryKey(['id']);

        $table->addColumn('fk_formato', 'integer');
        $table->addIndex(['fk_formato'], 'i_fk_formato');

        $table->addColumn('fk_contador', 'integer');
        $table->addIndex(['fk_contador'], 'i_fk_contador');

        $table->addColumn('label', 'string');
        $table->addColumn('name', 'string');
        $table->addColumn('show_anonymous', 'boolean', ['default' => 0]);
        $table->addColumn('show_label', 'boolean', ['default' => 1]);
        $table->addColumn('rad_email', 'boolean', ['default' => 0]);

        $table->addColumn('active', 'boolean', [
            'default' => 1
        ]);
    }

    public function tablePqrBackup(Table $table)
    {
        $table->addColumn('id', 'integer', [
            'autoincrement' => true
        ]);
        $table->setPrimaryKey(['id']);

        $table->addColumn('fk_documento', 'integer');
        $table->addIndex(['fk_documento'], 'i_fk_documento');

        $table->addColumn('fk_pqr', 'integer');
        $table->addIndex(['fk_pqr'], 'i_fk_pqr');

        $table->addColumn('data_json', 'text', [
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_general_ci'
        ]);
    }

    public function tablePqrResponseTemplate(Table $table)
    {
        $table->addColumn('id', 'integer', [
            'autoincrement' => true
        ]);
        $table->setPrimaryKey(['id']);

        $table->addColumn('name', 'string');
        $table->addColumn('content', 'text', [
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_general_ci'
        ]);
        $table->addColumn('system', 'boolean', [
            'notnull' => false,
            'default' => 0
        ]);
    }

    public function tablePqrNotify(Table $table)
    {
        $table->addColumn('id', 'integer', [
            'autoincrement' => true
        ]);
        $table->setPrimaryKey(['id']);

        $table->addColumn('fk_funcionario', 'integer');
        $table->addIndex(['fk_funcionario'], 'i_fk_funcionario');

        $table->addColumn('fk_pqr_form', 'integer');
        $table->addIndex(['fk_pqr_form'], 'i_fk_pqr_form');

        $table->addColumn('email', 'boolean', [
            'default' => false
        ]);
        $table->addColumn('notify', 'boolean', [
            'default' => false
        ]);
    }

    public function tablePqrHistory(Table $table)
    {
        $table->addColumn('id', 'integer', [
            'autoincrement' => true
        ]);
        $table->setPrimaryKey(['id']);

        $table->addColumn('idft', 'integer');
        $table->addColumn('fecha', 'datetime');
        $table->addColumn('nombre_funcionario', 'string', [
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_general_ci'
        ]);
        $table->addColumn('descripcion', 'text', [
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_general_ci'
        ]);
    }

    public function down(Schema $schema): void
    {
        $data = [
            'pqr_form_fields',
            'pqr_html_fields',
            'pqr_forms',
            'pqr_backups',
            'pqr_response_templates',
            'pqr_notifications',
            'pqr_history'
        ];

        foreach ($data as $table) {
            if ($schema->hasTable($table)) {
                $schema->dropTable($table);
            }
        }
    }
}
