<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Resources\migrations;

use App\Bundles\pqr\Services\models\PqrForm;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220701154843 extends AbstractMigration
{
    public function getDescription(): string
    {
        //TODO: Se puede borrar
        return 'Se agrega nuevo campo para habilitar filtros por dependencia';
    }

    public function up(Schema $schema): void
    {
        $this->connection->update('busqueda_condicion', [
            'codigo_where' => "sys_estado='PENDIENTE' {*filter_pqr_admin@PENDIENTE*}"
        ], [
            'etiqueta_condicion' => PqrForm::NOMBRE_REPORTE_PENDIENTE
        ]);


        $this->connection->update('busqueda_condicion', [
            'codigo_where' => "sys_estado='PROCESO' {*filter_pqr_admin@PROCESO*}"
        ], [
            'etiqueta_condicion' => PqrForm::NOMBRE_REPORTE_PROCESO
        ]);

        $this->connection->update('busqueda_condicion', [
            'codigo_where' => "sys_estado='TERMINADO' {*filter_pqr_admin@TERMINADO*}"
        ], [
            'etiqueta_condicion' => PqrForm::NOMBRE_REPORTE_TERMINADO
        ]);

        $table = $schema->getTable('pqr_forms');
        if (!$table->hasColumn('enable_filter_dep')) {
            $table->addColumn('enable_filter_dep', 'boolean', [
                'default' => 0
            ]);
        }

    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
