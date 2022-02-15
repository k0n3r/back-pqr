<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Resources\migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220214223955 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crea el reporte de la cantidad de pqr por dependencia';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $sql = "SELECT idbusqueda FROM busqueda WHERE nombre = 'reporte_pqr' AND estado = 1";
        $idbusqueda = $this->connection->fetchOne($sql);

        $info = '[{"title":"RADICADO","field":"{*verDocumento@iddocumento,numero*}","align":"center"},
                {"title":"FECHA","field":"{*dateRadication@fecha*}","align":"center"},
                {"title":"E-MAIL","field":"{*sys_email*}","align":"center"},
                {"title":"TIPO","field":"{*getValueSysTipo@iddocumento,sys_tipo*}","align":"center"},
                {"title":"ESTADO","field":"{*sys_estado*}","align":"center"}]';
        
        $this->connection->insert('busqueda_componente', [
            'busqueda_idbusqueda' => $idbusqueda,
            'url' => 'views/buzones/grilla.php',
            'etiqueta' => 'Total PQRS por dependencias',
            'nombre' => 'rep_total_pqr_depen',
            'orden' => 1,
            'info' => $info,
            'campos_adicionales' => 'v.sys_estado, v.iddocumento, v.numero, v.fecha, v.sys_tipo, v.sys_email',
            'ordenado_por' => 'v.numero',
            'direccion' => 'DESC',
            'agrupado_por' => '',
            'llave' => 'v.iddocumento',
            'ruta_libreria' => 'src/Bundles/pqr/formatos/pqr/reporteFunciones.php,src/Bundles/pqr/formatos/reporteFuncionesGenerales.php',
            'ruta_libreria_pantalla' => 'views/modules/pqr/formatos/pqr/reporteAcciones.js'
        ]);
        $sql = "SELECT idbusqueda_componente FROM busqueda_componente WHERE nombre = 'rep_total_pqr_depen'";
        $idbusqueda_componente = $this->connection->fetchOne($sql);
        $this->connection->insert('busqueda_condicion', [
            'fk_busqueda_componente' => $idbusqueda_componente,
            'codigo_where' => "{*filter_pqr*}",
            'etiqueta_condicion' => 'rep_total_pqr_depen'
        ]);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->connection->delete('busqueda_componente', [
            'nombre' => 'rep_total_pqr_depen'
        ]);

        $this->connection->delete('busqueda_condicion', [
            'etiqueta_condicion' => 'rep_total_pqr_depen'
        ]);
    }
}
