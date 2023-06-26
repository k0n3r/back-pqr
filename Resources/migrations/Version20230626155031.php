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

        $sql = "SELECT idgrafico FROM grafico WHERE nombre like 'pqr_oportunidad_resp'";
        $id = $this->connection->fetchOne($sql);

        if (!$id) {
            $sql = "SELECT idbusqueda_componente FROM busqueda_componente WHERE nombre like 'rep_todos_pqr'";
            $idComponente = $this->connection->fetchOne($sql);

            $sql = "SELECT idpantalla_grafico FROM pantalla_grafico WHERE nombre like 'PQRSF'";
            $idPantalla = $this->connection->fetchOne($sql);

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


    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
