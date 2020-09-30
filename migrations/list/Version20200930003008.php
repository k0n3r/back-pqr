<?php

declare(strict_types=1);

namespace Saia\Pqr\migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200930003008 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Se crea tabla que almacena la informacion de las notificaciones';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable('pqr_noty_messages');

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
        if ($schema->hasTable('pqr_noty_messages')) {
            $schema->dropTable('pqr_noty_messages');
        }
    }
}
