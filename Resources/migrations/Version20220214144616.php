<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Resources\migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220214144616 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crea el reporte PQRS por Dependencia';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $sql = "SELECT idbusqueda FROM busqueda WHERE nombre = 'reporte_pqr' AND estado = 1";
        $idbusqueda = $this->connection->fetchOne($sql);
        $info = '[{"title":"DEPENDENCIA","field":"{*getDependencia@sys_dependencia*}","align":"center"},
                {"title":"CANTIDAD","field":"{*getCantidad@sys_dependencia*}","align":"center"}]';
        
        $this->connection->insert('busqueda_componente', [
            'busqueda_idbusqueda' => $idbusqueda,
            'url' => 'views/buzones/grilla.php',
            'etiqueta' => 'PQRS por Dependencia',
            'nombre' => 'rep_dependencia_pqr',
            'orden' => 4,
            'info' => $info,
            'campos_adicionales' => 'v.sys_dependencia',
            'ordenado_por' => 'v.sys_dependencia',
            'direccion' => 'ASC',
            'agrupado_por' => 'v.sys_dependencia',
            'llave' => 'v.iddocumento',
            'ruta_libreria' => 'src/Bundles/pqr/formatos/pqr/reporteFunciones.php',
            'ruta_libreria_pantalla' => 'views/modules/pqr/formatos/pqr/reporteAcciones.js'
        ]);
        $sql = "SELECT idbusqueda_componente FROM busqueda_componente WHERE nombre = 'rep_dependencia_pqr'";
        $idbusqueda_componente = $this->connection->fetchOne($sql);
        $this->connection->insert('busqueda_condicion', [
            'fk_busqueda_componente' => $idbusqueda_componente,
            'codigo_where' => "sys_dependencia != ''",
            'etiqueta_condicion' => 'rep_dependencia_pqr'
        ]);

        $sql = "SELECT idmodulo FROM modulo WHERE nombre = 'reporte_pqr'";
        $cod_padre = $this->connection->fetchOne($sql);

        $enlace = 'views/dashboard/kaiten_dashboard.php?panels=[{"kConnector": "iframe","url": "views/buzones/grilla.php?idbusqueda_componente='. $idbusqueda_componente . '"}]';
        $this->connection->insert('modulo', [
            'pertenece_nucleo' => 0,
            'nombre' => 'rep_dependencia_pqr',
            'tipo' => 2,
            'imagen' => 'fa fa-bar-chart-o',
            'etiqueta' => 'PQRS por Dependencia',
            'enlace' => $enlace,
            'cod_padre' => $cod_padre,
            'orden' => 4,
            'asignable' => 1,
            'tiene_hijos' => 0
        ]);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs.

        $this->connection->delete('busqueda_componente', [
            'nombre' => 'rep_dependencia_pqr'
        ]);

        $this->connection->delete('busqueda_condicion', [
            'etiqueta_condicion' => 'rep_dependencia_pqr'
        ]);

        $this->connection->delete('modulo', [
            'nombre' => 'rep_dependencia_pqr'
        ]);
    }
}
