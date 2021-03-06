<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Resources\migrations;

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
        return 'Creacion de tablas';
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

        $table6 = $schema->createTable('pqr_notifications');
        $this->tablePqrNotify($table6);

        $table7 = $schema->createTable('pqr_history');
        $this->tablePqrHistory($table7);

        $table8 = $schema->createTable('pqr_noty_messages');
        $this->tablePqrNotyMessages($table8);
    }

    public function tablePqrFormFields(Table $table)
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

        $table->addColumn('setting', 'text');

        $table->addColumn('fk_pqr_html_field', 'integer');
        $table->addIndex(['fk_pqr_html_field'], 'i_fk_pqr_html_field');

        $table->addColumn('fk_pqr_form', 'integer');
        $table->addIndex(['fk_pqr_form'], 'i_fk_pqr_form');

        $table->addColumn('fk_campos_formato', 'integer', [
            'default' => 0,
            'notnull' => false
        ]);
        $table->addIndex(['fk_campos_formato'], 'i_fk_campos_formato');


        $table->addColumn('is_system', 'boolean', [
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
            'length' => 50
        ]);

        $table->addColumn('type', 'string', [
            'length' => 50
        ]);

        $table->addColumn('type_saia', 'string', [
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
        $table->addColumn('response_configuration', 'text', [
            'notnull' => false
        ]);

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

        $table->addColumn('data_json', 'text');
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
        $table->addColumn('fk_funcionario', 'integer');
        $table->addColumn('tipo', 'integer');
        $table->addColumn('idfk', 'integer', [
            'default' => 0
        ]);
        $table->addColumn('descripcion', 'text');
    }

    public function tablePqrNotyMessages(Table $table)
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

        $table->addColumn('description', 'text', [
            'notnull' => false
        ]);

        $table->addColumn('subject', 'text', [
            'notnull' => false
        ]);

        $table->addColumn('message_body', 'text', [
            'notnull' => false
        ]);

        $table->addColumn('type', 'integer', [
            'comment' => '1:Noty,2:Email',
            'default' => 1
        ]);

        $table->addColumn('active', 'boolean', [
            'default' => 1
        ]);
    }

    public function down(Schema $schema): void
    {
        $data = [
            'pqr_form_fields',
            'pqr_html_fields',
            'pqr_forms',
            'pqr_backups',
            'pqr_notifications',
            'pqr_history',
            'pqr_noty_messages'
        ];

        foreach ($data as $table) {
            if ($schema->hasTable($table)) {
                $schema->dropTable($table);
            }
        }
    }
}
