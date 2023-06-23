<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Resources\migrations;

use App\Bundles\pqr\Services\PqrService;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230623201856 extends AbstractMigration
{
    public function getDescription(): string
    {
        //TODO: Se puede borrar
        return 'Se adiciona filtros a los indicadores y se ajusta el alias a la vista';
    }

    public function up(Schema $schema): void
    {
        $sql = "SELECT idbusqueda_componente FROM busqueda_componente WHERE nombre LIKE 'rep_todos_pqr'";
        $id = $this->connection->fetchOne($sql);

        $sql = "UPDATE grafico SET fk_busqueda_componente=$id WHERE nombre IN ('pqr_tipo','pqr_estado','" . PqrService::NAME_DEPENDENCY_GRAPH . "')";
        $this->connection->executeStatement($sql);

        $sql = "SELECT idgrafico FROM grafico WHERE nombre like 'pqr_estado'";
        $idgrafico = $this->connection->fetchOne($sql);

        $this->connection->update('grafico_serie', [
            'query' => 'SELECT sys_estado,count(sys_estado) AS cantidad FROM vpqr v GROUP BY sys_estado'
        ], [
            'fk_grafico' => $idgrafico
        ]);


    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
