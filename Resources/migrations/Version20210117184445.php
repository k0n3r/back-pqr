<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Resources\migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use App\Bundles\pqr\Services\models\PqrForm;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210117184445 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Cambios sobre DB migracion a V8.2';
    }

    public function up(Schema $schema): void
    {
        $this->connection->update('busqueda_componente', [
            'busqueda_avanzada' => 'views/modules/pqr/formatos/pqr/busqueda.php',
            'ruta_libreria' => 'src/Bundles/pqr/formatos/pqr/reporteFunciones.php,src/Bundles/pqr/formatos/reporteFuncionesGenerales.php',
            'ruta_libreria_pantalla' => 'views/modules/pqr/formatos/pqr/reporteAcciones.js'
        ], [
            'nombre' => PqrForm::NOMBRE_REPORTE_PENDIENTE
        ]);

        $this->connection->update('busqueda_componente', [
            'busqueda_avanzada' => 'views/modules/pqr/formatos/pqr/busqueda.php',
            'ruta_libreria' => 'src/Bundles/pqr/formatos/pqr/reporteFunciones.php,src/Bundles/pqr/formatos/reporteFuncionesGenerales.php',
            'ruta_libreria_pantalla' => 'views/modules/pqr/formatos/pqr/reporteAcciones.js'
        ], [
            'nombre' => PqrForm::NOMBRE_REPORTE_PROCESO
        ]);

        $this->connection->update('busqueda_componente', [
            'busqueda_avanzada' => 'views/modules/pqr/formatos/pqr/busqueda.php',
            'ruta_libreria' => 'src/Bundles/pqr/formatos/pqr/reporteFunciones.php,src/Bundles/pqr/formatos/reporteFuncionesGenerales.php',
            'ruta_libreria_pantalla' => 'views/modules/pqr/formatos/pqr/reporteAcciones.js'
        ], [
            'nombre' => PqrForm::NOMBRE_REPORTE_TERMINADO
        ]);

        $this->connection->update('busqueda_componente', [
            'busqueda_avanzada' => 'views/modules/pqr/formatos/pqr/busqueda.php',
            'ruta_libreria' => 'src/Bundles/pqr/formatos/pqr/reporteFunciones.php,src/Bundles/pqr/formatos/reporteFuncionesGenerales.php',
            'ruta_libreria_pantalla' => 'views/modules/pqr/formatos/pqr/reporteAcciones.js'
        ], [
            'nombre' => PqrForm::NOMBRE_REPORTE_TODOS
        ]);

        $this->connection->update('busqueda_componente', [
            'url' => 'views/buzones/grilla.php',
            'ruta_libreria' => 'src/Bundles/pqr/formatos/pqr_respuesta/reporteFunciones.php,src/Bundles/pqr/formatos/reporteFuncionesGenerales.php',
            'ruta_libreria_pantalla' => 'views/modules/pqr/formatos/pqr_respuesta/reporteAcciones.js'
        ], [
            'nombre' => 'respuesta_pqr'
        ]);

        $this->connection->update('busqueda_componente', [
            'url' => 'views/buzones/grilla.php',
            'ruta_libreria' => 'src/Bundles/pqr/formatos/pqr_calificacion/reporteFunciones.php,src/Bundles/pqr/formatos/reporteFuncionesGenerales.php'
        ], [
            'nombre' => 'calificacion_pqr'
        ]);

        $this->connection->update('tarea', [
            'class_name' => 'App\Bundles\pqr\Services\controllers\TaskEvents'
        ], [
            'class_name' => 'Saia\Pqr\controllers\TaskEvents'
        ]);

        $this->connection->update('grafico', [
            'modelo' => 'App\Bundles\pqr\formatos\pqr\FtPqr'
        ], [
            'modelo' => 'Saia\Pqr\formatos\pqr\FtPqr'
        ]);


        if ($schema->hasTable('pqr_migrations')) {
            $schema->dropTable('pqr_migrations');
        }

        /*      
        INSERT INTO migrations (version)  VALUES ('App\\Bundles\\pqr\\Resources\\migrations\\Version20191224165528');
        INSERT INTO migrations (version)  VALUES ('App\\Bundles\\pqr\\Resources\\migrations\\Version20200103155146');
        INSERT INTO migrations (version)  VALUES ('App\\Bundles\\pqr\\Resources\\migrations\\Version20200103161511');
        INSERT INTO migrations (version)  VALUES ('App\\Bundles\\pqr\\Resources\\migrations\\Version20200226192642');
        INSERT INTO migrations (version)  VALUES ('App\\Bundles\\pqr\\Resources\\migrations\\Version20200310032827');
        INSERT INTO migrations (version)  VALUES ('App\\Bundles\\pqr\\Resources\\migrations\\Version20200321234633');
        INSERT INTO migrations (version)  VALUES ('App\\Bundles\\pqr\\Resources\\migrations\\Version20200406213013');
        */
    }


    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
