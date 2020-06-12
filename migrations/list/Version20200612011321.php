<?php

declare(strict_types=1);

namespace Saia\Pqr\migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200612011321 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Se crea la tabla nofificaciones';
    }

    public function up(Schema $schema): void
    {
        $table6 = $schema->createTable('pqr_notifications');
        $this->tablePqrNotify($table6);
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

    public function down(Schema $schema): void
    {
        $schema->dropTable('pqr_notifications');
    }
}
