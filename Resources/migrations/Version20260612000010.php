<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Resources\migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Agrega el índice sobre pqr_history.fk_funcionario para soportar la
 * relación Doctrine ManyToOne hacia Funcionario (alineado con pqr_notifications).
 */
final class Version20260612000010 extends AbstractMigration
{
    private const string TABLE = 'pqr_history';
    private const string INDEX = 'ipqr_histfk_funcio';

    public function getDescription(): string
    {
        return 'Índice en pqr_history.fk_funcionario para la relación ManyToOne con Funcionario';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable(self::TABLE);
        if (!$table->hasIndex(self::INDEX)) {
            $table->addIndex(['fk_funcionario'], self::INDEX);
        }
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable(self::TABLE);
        if ($table->hasIndex(self::INDEX)) {
            $table->dropIndex(self::INDEX);
        }
    }
}
