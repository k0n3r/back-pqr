<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Resources\migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210411151159 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Se quita tipo descripcion en campo contenido de respuesta pqr';
    }

    private function getFormatId(): int
    {
        $sql = "select idformato from formato where nombre LIKE 'pqr_respuesta'";
        return (int)$this->connection->fetchOne($sql);
    }

    public function up(Schema $schema): void
    {
        $formatId = $this->getFormatId();

        $this->connection->update('campos_formato', [
            'acciones' => 'a,e'
        ], [
            'nombre'            => 'contenido',
            'formato_idformato' => $formatId
        ]);
    }

    public function down(Schema $schema): void
    {
        $formatId = $this->getFormatId();

        $this->connection->update('campos_formato', [
            'acciones' => 'p,a,e'
        ], [
            'nombre'            => 'contenido',
            'formato_idformato' => $formatId
        ]);
    }
}
