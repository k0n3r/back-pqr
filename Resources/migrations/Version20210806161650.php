<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Resources\migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210806161650 extends AbstractMigration
{
    public function getDescription(): string
    {
        //TODO: Se puede borrar
        return 'Se actualiza el campo (con copia) de la ft_pqr_respuesta a multiple';
    }

    public function up(Schema $schema): void
    {
        $sql = "SELECT idformato FROM formato WHERE nombre LIKE 'pqr_respuesta'";
        $idformato = $this->connection->fetchOne($sql);
        $this->connection->update('campos_formato', [
            'opciones' => '{"tipo_seleccion":"multiple","tipo":true,"nombre":true,"correo":true,"tipo_identificacion":true,"identificacion":true,"ciudad":true,"titulo":true,"cargo":false,"direccion":true,"telefono":true,"sede":false,"empresa":false}'
        ], [
            'nombre'            => 'copia',
            'formato_idformato' => $idformato
        ]);

    }

    public function down(Schema $schema): void
    {
    }
}
