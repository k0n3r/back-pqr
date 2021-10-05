<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Resources\migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211005000341 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Se crea campo para mostrar u ocultar campo vacios';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('pqr_forms');
        if (!$table->hasColumn('show_empty')) {
            $table->addColumn('show_empty', 'integer', [
                'default' => 1
            ]);
        }

    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('pqr_forms');
        if ($table->hasColumn('show_empty')) {
            $table->dropColumn('show_empty');
        }
    }
}
