<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Resources\migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230622231509 extends AbstractMigration
{
    public function getDescription(): string
    {
        //TODO: Se puede borrar
        return 'Se invierten las consultas sql de los graficos de pqr gestion y servicio';
    }

    public function up(Schema $schema): void
    {

        $sql = "SELECT idgrafico FROM grafico WHERE nombre like 'pqr_calificacion_gestion'";
        $idgraficoGestion = $this->connection->fetchOne($sql);

        $sql = "SELECT idgrafico FROM grafico WHERE nombre like 'pqr_calificacion_servicio'";
        $idgraficoServicio = $this->connection->fetchOne($sql);

        $this->connection->update('grafico_serie', [
            'query' => 'SELECT c.valor,count(c.valor) AS cantidad FROM vpqr_calificacion v,campo_opciones c WHERE v.experiencia_gestion=c.idcampo_opciones GROUP BY c.valor'
        ], [
            'fk_grafico' => $idgraficoGestion
        ]);

        $this->connection->update('grafico_serie', [
            'query' => 'SELECT c.valor,count(c.valor) AS cantidad FROM vpqr_calificacion v,campo_opciones c WHERE v.experiencia_servicio=c.idcampo_opciones GROUP BY c.valor'
        ], [
            'fk_grafico' => $idgraficoServicio
        ]);

    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
