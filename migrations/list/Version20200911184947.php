<?php

declare(strict_types=1);

namespace Saia\Pqr\migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200911184947 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Se actualizan los campos para de historial para el timeLine';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('pqr_history');
        $table->dropColumn('nombre_funcionario');
        $table->addColumn('fk_funcionario', 'integer');
        $table->addColumn('tipo', 'integer');
        $table->addColumn('idfk', 'integer', [
            'default' => 0
        ]);
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('pqr_history');
        $table->dropColumn('fk_funcionario');
        $table->dropColumn('tipo');
        $table->dropColumn('idfk');
        $table->addColumn('nombre_funcionario', 'string');
    }
}
