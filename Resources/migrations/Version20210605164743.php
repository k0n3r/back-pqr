<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Resources\migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210605164743 extends AbstractMigration
{
    public function getDescription(): string
    {
        //TODO: Se puede borrar
        return 'Se crea campo que define el tiempo de respuesta';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('pqr_forms');
        if (!$table->hasColumn('fk_field_time')) {
            $table->addColumn('fk_field_time', 'integer', [
                'default' => 0,
                'comment' => 'idcampos_formato'
            ]);
        }

        if (!$schema->hasTable('pqr_response_times')) {
            $table2 = $schema->createTable('pqr_response_times');
            $this->tablePqrResponseTime($table2);
        }
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('pqr_forms');
        if ($table->hasColumn('pqr_forms')) {
            $table->dropColumn('fk_field_time');
        }

        if ($schema->hasTable('pqr_response_times')) {
            $schema->dropTable('pqr_response_times');
        }
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
}
