<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Resources\migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240408223049 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Se actualiza el mensaje al radicar una pqr';
    }

    public function up(Schema $schema): void
    {
        //TODO: Se puede borrar
        $fields = Version20200103161511::getDataPqrNotyMessages();
        foreach ($fields as $field) {
            $this->connection->update('pqr_noty_messages', $field, [
                'name' => $field['name']
            ]);
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
