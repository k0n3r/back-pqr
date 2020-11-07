<?php

declare(strict_types=1);

namespace Saia\Pqr\migrations\lista;

use Saia\Pqr\models\PqrForm;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201107032208 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Se ordenan los reportes de forma ascendente';
    }

    public function up(Schema $schema): void
    {
        $this->connection->update('busqueda_componente', [
            'direccion' => 'ASC'
        ], [
            'nombre' => PqrForm::NOMBRE_REPORTE_PENDIENTE
        ]);

        $this->connection->update('busqueda_componente', [
            'direccion' => 'ASC'
        ], [
            'nombre' => PqrForm::NOMBRE_REPORTE_PROCESO
        ]);

        $this->connection->update('busqueda_componente', [
            'direccion' => 'ASC'
        ], [
            'nombre' => PqrForm::NOMBRE_REPORTE_TERMINADO
        ]);
    }

    public function down(Schema $schema): void
    {
        $this->connection->update('busqueda_componente', [
            'direccion' => 'DESC'
        ], [
            'nombre' => PqrForm::NOMBRE_REPORTE_PENDIENTE
        ]);

        $this->connection->update('busqueda_componente', [
            'direccion' => 'DESC'
        ], [
            'nombre' => PqrForm::NOMBRE_REPORTE_PROCESO
        ]);

        $this->connection->update('busqueda_componente', [
            'direccion' => 'DESC'
        ], [
            'nombre' => PqrForm::NOMBRE_REPORTE_TERMINADO
        ]);
    }
}
