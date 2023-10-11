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

        $table9 = $schema->createTable('pqr_response_times');
        $this->tablePqrResponseTime($table9);
    }

    private function tablePqrFormFields(Table $table)
    {

        $table->addColumn('id', 'integer', [
            'autoincrement' => true
        ]);
        $table->setPrimaryKey(['id']);

        $table->addColumn('name', 'string', [
            'length' => 50
        ]);

        $table->addColumn('label', 'string', [
            'length' => 400
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

    private function tablePqrHtmlFields(Table $table)
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

    private function tablePqrForm(Table $table)
    {
        $table->addColumn('id', 'integer', [
            'autoincrement' => true
        ]);
        $table->setPrimaryKey(['id']);

        $table->addColumn('fk_formato', 'integer');
        $table->addIndex(['fk_formato'], 'ipqr_formsfk_format');

        $table->addColumn('fk_contador', 'integer');
        $table->addIndex(['fk_contador'], 'ipqr_formsfk_contad');

        $table->addColumn('label', 'string');
        $table->addColumn('name', 'string');
        $table->addColumn('show_anonymous', 'boolean', ['default' => 0]);
        $table->addColumn('show_label', 'boolean', ['default' => 1]);
        $table->addColumn('show_empty', 'boolean', ['default' => 1]);
        $table->addColumn('response_configuration', 'text', [
            'notnull' => false
        ]);

        $table->addColumn('active', 'boolean', [
            'default' => 1
        ]);

        $table->addColumn('fk_field_time', 'integer', [
            'default' => 0,
            'comment' => 'idcampos_formato'
        ]);

        $table->addColumn('enable_filter_dep', 'boolean', ['default' => 0]);

        $table->addColumn('description_field', 'integer', [
            'length'  => 11,
            'default' => 0
        ]);
    }

    private function tablePqrBackup(Table $table)
    {
        $table->addColumn('id', 'integer', [
            'autoincrement' => true
        ]);
        $table->setPrimaryKey(['id']);

        $table->addColumn('fk_documento', 'integer');
        $table->addIndex(['fk_documento'], 'ipqr_backufk_docume');

        $table->addColumn('fk_pqr', 'integer');
        $table->addIndex(['fk_pqr'], 'ipqr_backufk_pqr');

        $table->addColumn('data_json', 'text');
    }

    private function tablePqrNotify(Table $table)
    {
        $table->addColumn('id', 'integer', [
            'autoincrement' => true
        ]);
        $table->setPrimaryKey(['id']);

        $table->addColumn('fk_funcionario', 'integer');
        $table->addIndex(['fk_funcionario'], 'ipqr_notiffk_funcio');

        $table->addColumn('fk_pqr_form', 'integer');
        $table->addIndex(['fk_pqr_form'], 'ipqr_notiffk_pqr_fo');

        $table->addColumn('email', 'boolean', [
            'default' => false
        ]);
        $table->addColumn('notify', 'boolean', [
            'default' => false
        ]);
    }

    private function tablePqrHistory(Table $table)
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

    private function tablePqrNotyMessages(Table $table)
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

    private function tablePqrResponseTime(Table $table)
    {
        $table->addColumn('id', 'integer', [
            'autoincrement' => true
        ]);
        $table->setPrimaryKey(['id']);

        $table->addColumn('fk_campo_opciones', 'integer');
        $table->addColumn('fk_sys_tipo', 'integer', [
            'comment' => 'idcampo_opciones'
        ]);
        $table->addColumn('number_days', 'integer');
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
            'pqr_noty_messages',
            'pqr_response_times'
        ];

        foreach ($data as $table) {
            if ($schema->hasTable($table)) {
                $schema->dropTable($table);
            }
        }
    }
}
