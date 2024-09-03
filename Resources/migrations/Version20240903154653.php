<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Resources\migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240903154653 extends AbstractMigration
{
    public function getDescription(): string
    {
        //TODO: Se puede eliminar
        return 'Se adiciona los valores por defecto';
    }

    public function up(Schema $schema): void
    {
        $chanels = json_encode([
            'cFISICO',
            'cTELEFONICO',
            'cREDES'
        ]);

        $sql = "UPDATE pqr_forms SET canal_recepcion='$chanels'";
        $this->connection->executeStatement($sql);

    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
