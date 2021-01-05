<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Resources\migrations;

use App\Bundles\pqr\Services\models\PqrForm;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201121164942 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Se actualizan el campo url de la busqueda componente';
    }

    public function up(Schema $schema): void
    {
        $reports = [
            PqrForm::NOMBRE_REPORTE_PENDIENTE,
            PqrForm::NOMBRE_REPORTE_PROCESO,
            PqrForm::NOMBRE_REPORTE_TERMINADO,
            PqrForm::NOMBRE_REPORTE_TODOS,
        ];

        foreach ($reports as $reportName) {
            $this->connection->update('busqueda_componente', [
                'url' => 'views/buzones/grilla.php'
            ], [
                'nombre' => $reportName
            ]);
        }
    }

    public function down(Schema $schema): void
    {
    }
}
