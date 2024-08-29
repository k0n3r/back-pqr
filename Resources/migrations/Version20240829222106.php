<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Resources\migrations;

use App\Bundles\pqr\Services\models\PqrForm;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240829222106 extends AbstractMigration
{
    use TDependencyReport;

    public function getDescription(): string
    {
        //TODO Se puede eliminar
        return 'Se adiciona busqueda al reporte de busqueda por dependencia';
    }

    public function up(Schema $schema): void
    {

        $nombreComponente = PqrForm::NOMBRE_REPORTE_POR_DEPENDENCIA;
        $sql = "SELECT busqueda_idbusqueda FROM busqueda_componente WHERE lower(nombre) LIKE lower('$nombreComponente')";
        $id = (int)$this->connection->fetchOne($sql);
        if ($id) {
            $this->createComponentePorDependencia($id);
        }

    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
