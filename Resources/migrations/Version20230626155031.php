<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Resources\migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Saia\models\grafico\Grafico;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230626155031 extends AbstractMigration
{
    public function getDescription(): string
    {
        //TODO: Se puede borrar
        return 'Tarea Programada para actualizar el campo sys_oportuno, graficos nuevos';
    }

    public function up(Schema $schema): void
    {
        $this->createCrontab();
        $this->createGraphs();

        $sql = "UPDATE grafico SET mostrar_etiqueta=1 WHERE nombre LIKE 'pqr_%' AND nombre NOT IN ('pqr_calificacion_gestion','pqr_calificacion_servicio','pqr_oportunidad_resp')";
        $this->connection->executeStatement($sql);

        $sql = "UPDATE grafico SET mostrar_etiqueta=0 WHERE nombre IN ('pqr_calificacion_gestion','pqr_calificacion_servicio','pqr_oportunidad_resp')";
        $this->connection->executeStatement($sql);
    }

    private function createCrontab()
    {
        $sql = "SELECT idcrontab FROM crontab WHERE clase like '%ChangeStatusOfOportunoField%'";
        $id = $this->connection->fetchOne($sql);

        if (!$id) {
            $this->connection->insert('crontab', [
                'clase'            => 'App\\Bundles\\pqr\\Services\\crontab\\ChangeStatusOfOportunoField',
                'descripcion'      => 'Actualiza el campo sys_oportuno de las PQRS',
                'email_notifica'   => 'soporte@cerok.com',
                'estado'           => 1,
                'pertenece_nucleo' => 0,
            ]);
        }
    }

    private function createGraphs()
    {
        $sql = "SELECT idbusqueda_componente FROM busqueda_componente WHERE nombre like 'rep_todos_pqr'";
        $idComponente = $this->connection->fetchOne($sql);

        $sql = "SELECT idpantalla_grafico FROM pantalla_grafico WHERE nombre like 'PQRSF'";
        $idPantalla = $this->connection->fetchOne($sql);

        $sql = "SELECT idgrafico FROM grafico WHERE nombre like 'pqr_oportunidad_resp'";
        $id = $this->connection->fetchOne($sql);

        if (!$id) {
            $this->connection->insert('grafico', [
                'fk_busqueda_componente' => $idComponente,
                'fk_pantalla_grafico'    => $idPantalla,
                'nombre'                 => 'pqr_oportunidad_resp',
                'tipo'                   => Grafico::PIE,
                'configuracion'          => null,
                'estado'                 => 1,
                'modelo'                 => 'App\\Bundles\\pqr\\formatos\\pqr\\FtPqr',
                'columna'                => '-',
                'titulo_x'               => 'Estado',
                'titulo_y'               => 'Cantidad',
                'busqueda'               => 'views/modules/pqr/formatos/pqr/busqueda_grafico.html',
                'librerias'              => null,
                'titulo'                 => 'Oportunidad en las respuestas',
                'mostrar_etiqueta'       => 0
            ]);
            $idgrafico = $this->connection->lastInsertId('grafico');

            $this->connection->insert('grafico_serie', [
                'fk_grafico' => $idgrafico,
                'query'      => 'SELECT sys_oportuno,count(sys_oportuno) AS cantidad FROM vpqr v GROUP BY sys_oportuno',
                'etiqueta'   => 'Estado'
            ]);
        }

        $sql = "SELECT idgrafico FROM grafico WHERE nombre like 'pqr_canal_recepcion'";
        $id = $this->connection->fetchOne($sql);

        if (!$id) {

            $this->connection->insert('grafico', [
                'fk_busqueda_componente' => $idComponente,
                'fk_pantalla_grafico'    => $idPantalla,
                'nombre'                 => 'pqr_canal_recepcion',
                'tipo'                   => Grafico::PIE,
                'configuracion'          => null,
                'estado'                 => 1,
                'modelo'                 => 'App\\Bundles\\pqr\\formatos\\pqr\\FtPqr',
                'columna'                => '-',
                'titulo_x'               => 'Canal',
                'titulo_y'               => 'Cantidad',
                'busqueda'               => 'views/modules/pqr/formatos/pqr/busqueda_grafico.html',
                'librerias'              => null,
                'titulo'                 => 'Canal de recepción',
                'mostrar_etiqueta'       => 1
            ]);
            $idgrafico = $this->connection->lastInsertId('grafico');

            $this->connection->insert('grafico_serie', [
                'fk_grafico' => $idgrafico,
                'query'      => 'SELECT canal_recepcion,count(canal_recepcion) AS cantidad FROM vpqr v GROUP BY canal_recepcion',
                'etiqueta'   => 'Canal recepción'
            ]);
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
