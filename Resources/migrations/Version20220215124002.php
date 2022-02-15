<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Resources\migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220215124002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crea el grafico en torta de las dos preguntas';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $sql = "SELECT idpantalla_grafico FROM pantalla_grafico WHERE nombre = 'PQRSF'";
        $idpatalla_grafico = $this->connection->fetchOne($sql);
        //Grafico calificacion de la pqrs
        $this->connection->insert('grafico', [
            'fk_pantalla_grafico' => $idpatalla_grafico,
            'nombre' => 'calificacion_PQRSF',
            'tipo' => 2,
            'estado' => 1,
            'modelo' => 'App\Bundles\pqr\formatos\pqr\FtPqr',
            'columna' => '-',
            'titulo_x' => 'Calificacion PQRSF',
            'titulo_y' => 'Cantidad',
            'titulo' => 'Calificaciones PQRSF',
            'mostrar_etiqueta' => 1
        ]);
        
        $sql = "SELECT idgrafico FROM grafico WHERE nombre = 'calificacion_PQRSF'";
        $idgrafico = $this->connection->fetchOne($sql);
        $this->connection->insert('grafico_serie', [
            'fk_grafico' => $idgrafico,
            'query' => 'SELECT c.valor,count(c.valor) AS cantidad FROM ft_pqr_calificacion ft,campo_opciones c WHERE ft.experiencia_gestion=c.idcampo_opciones GROUP BY c.valor',
            'etiqueta' => 'Calificaciones PQRSF',
        ]);
        //Grafico calificacion de Experiencia global
        $this->connection->insert('grafico', [
            'fk_pantalla_grafico' => $idpatalla_grafico,
            'nombre' => 'calificacion_global_servicios',
            'tipo' => 2,
            'estado' => 1,
            'modelo' => 'App\Bundles\pqr\formatos\pqr\FtPqr',
            'columna' => '-',
            'titulo_x' => 'Calificacion Global Servicio',
            'titulo_y' => 'Cantidad',
            'titulo' => 'Calificación Global Servicio',
            'mostrar_etiqueta' => 1
        ]);
        
        $sql = "SELECT idgrafico FROM grafico WHERE nombre = 'calificacion_global_servicios'";
        $idgrafico = $this->connection->fetchOne($sql);
        $this->connection->insert('grafico_serie', [
            'fk_grafico' => $idgrafico,
            'query' => 'SELECT c.valor,count(c.valor) AS cantidad FROM ft_pqr_calificacion ft,campo_opciones c WHERE ft.experiencia_servicio=c.idcampo_opciones GROUP BY c.valor',
            'etiqueta' => 'Calificacion Global Servicio',
        ]);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->connection->delete('grafico', [
            'nombre' => 'calificacion_PQRSF'
        ]);

        $this->connection->delete('grafico_serie', [
            'etiqueta' => 'Calificaciones PQRSF'
        ]);

        $this->connection->delete('grafico', [
            'nombre' => 'calificacion_global_servicios'
        ]);

        $this->connection->delete('grafico_serie', [
            'etiqueta' => 'Calificacion Global Servicio'
        ]);
    }
}
