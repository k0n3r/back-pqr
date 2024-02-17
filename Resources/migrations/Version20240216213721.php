<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Resources\migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240216213721 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Se genera tabla para creacion de balanceo';
    }

    public function up(Schema $schema): void
    {
        //TODO: Se puede borrar
        if (!$schema->hasTable('pqr_balancer')) {
            $table = $schema->createTable('pqr_balancer');
            $this->crateTable($table);
        }

        $table2 = $schema->getTable('pqr_forms');
        if (!$table2->hasColumn('fk_field_balancer')) {
            $table2->addColumn('enable_balancer', 'boolean', ['default' => 0]);
            $table2->addColumn('fk_field_balancer', 'integer', [
                'default' => 0,
                'comment' => 'idcampos_formato'
            ]);
        }

    }

    private function crateTable(Table $table)
    {
        $table->addColumn('id', 'integer', [
            'autoincrement' => true
        ]);
        $table->setPrimaryKey(['id']);

        $table->addColumn('fk_campo_opciones', 'integer');
        $table->addColumn('fk_sys_tipo', 'integer', [
            'comment' => 'idcampo_opciones'
        ]);
        $table->addColumn('fk_grupo', 'integer');
        $table->addColumn('active', 'boolean', [
            'default' => 1
        ]);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
