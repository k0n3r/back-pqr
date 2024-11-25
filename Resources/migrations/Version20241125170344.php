<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Resources\migrations;

use App\Bundles\pqr\Services\models\PqrForm;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241125170344 extends AbstractMigration
{
    public function getDescription(): string
    {
        //TODO: Se puede eliminar
        return 'Se quita el v.sys_dependencia de campos adicionales puesto ya esta en la llave';
    }

    public function up(Schema $schema): void
    {
        $this->connection->update('busqueda_componente', [
            'campos_adicionales' => null
        ], [
            'nombre' => PqrForm::NOMBRE_REPORTE_POR_DEPENDENCIA
        ]);

    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
