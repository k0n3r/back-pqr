<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Resources\migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230602192203 extends AbstractMigration
{
    public function getDescription(): string
    {
        //TODO: Se puede borrar
        return 'Se actualiza la busqueda.php a busqueda.html';
    }

    public function up(Schema $schema): void
    {
        $sql = "UPDATE busqueda_componente SET busqueda_avanzada='views/modules/pqr/formatos/pqr/busqueda.html' WHERE busqueda_avanzada LIKE '%pqr%busqueda%'";
        $this->connection->executeStatement($sql);

    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
