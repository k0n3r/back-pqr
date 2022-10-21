<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Resources\migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221021021344 extends AbstractMigration
{

    public function getDescription(): string
    {
        //TODO: Se puede borrar
        return 'Se elimina el campo rad_email';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('pqr_forms');
        $table->dropColumn('rad_email');

    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
