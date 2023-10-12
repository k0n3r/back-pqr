<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Resources\migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230925163206 extends AbstractMigration
{
    public function getDescription(): string
    {
        //TODO: Se puede borrar
        return 'Se adiciona campo en la tabla pqr_forms';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('pqr_forms');
        if (!$table->hasColumn('description_field')) {
            $table->addColumn('description_field', 'integer', [
                'length'  => 11,
                'default' => 0
            ]);
        }
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('pqr_forms');
        $table->dropColumn('description_field');
    }
}
