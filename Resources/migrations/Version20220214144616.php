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
        return 'Crea reportes y graficos';
    }

    public function up(Schema $schema): void
    {
        return;
        // this up() migration is auto-generated, please modify it to your needs
        $this->reporteDependencia();
        $this->reporteEstadosPqrDependencia();
        $this->reporteCantidadPqrDependencia();
        $this->graficos();
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs.
        $this->downReporteDepencencia();
        $this->downReporteEstadosPqrDependencia();
        $this->downReporteCantidadPqrDependencia();
        $this->downGraficos();
        
    }

    public function reporteDependencia(){
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

    public function reporteEstadosPqrDependencia(){
        $sql = "SELECT idbusqueda FROM busqueda WHERE nombre = 'reporte_pqr' AND estado = 1";
        $idbusqueda = $this->connection->fetchOne($sql);

        $info = '[{"title":"DEPENDENCIA","field":"{*getDependencia@sys_dependencia*}","align":"center"},
                {"title":"CANTIDAD","field":"{*getCantidad@sys_dependencia*}","align":"center"},
                {"title":"PENDIENTES","field":"{*getPendientes@sys_dependencia*}","align":"center"},
                {"title":"RESUELTAS","field":"{*getResueltas@sys_dependencia*}","align":"center"}]';
        
        $this->connection->insert('busqueda_componente', [
            'busqueda_idbusqueda' => $idbusqueda,
            'url' => 'views/buzones/grilla.php',
            'etiqueta' => 'Estados de PQRS por Dependencia',
            'nombre' => 'rep_estado_depen_pqr',
            'orden' => 5,
            'info' => $info,
            'campos_adicionales' => 'v.sys_dependencia',
            'ordenado_por' => 'v.sys_dependencia',
            'direccion' => 'ASC',
            'agrupado_por' => 'v.sys_dependencia',
            'llave' => 'v.iddocumento',
            'ruta_libreria' => 'src/Bundles/pqr/formatos/pqr/reporteFunciones.php',
            'ruta_libreria_pantalla' => 'views/modules/pqr/formatos/pqr/reporteAcciones.js'
        ]);
        $sql = "SELECT idbusqueda_componente FROM busqueda_componente WHERE nombre = 'rep_estado_depen_pqr'";
        $idbusqueda_componente = $this->connection->fetchOne($sql);
        $this->connection->insert('busqueda_condicion', [
            'fk_busqueda_componente' => $idbusqueda_componente,
            'codigo_where' => "sys_dependencia != ''",
            'etiqueta_condicion' => 'rep_estado_depen_pqr'
        ]);

        $sql = "SELECT idmodulo FROM modulo WHERE nombre = 'reporte_pqr'";
        $cod_padre = $this->connection->fetchOne($sql);

        $enlace = 'views/dashboard/kaiten_dashboard.php?panels=[{"kConnector": "iframe","url": "views/buzones/grilla.php?idbusqueda_componente='. $idbusqueda_componente . '"}]';
        $this->connection->insert('modulo', [
            'pertenece_nucleo' => 0,
            'nombre' => 'rep_estado_depen_pqr',
            'tipo' => 2,
            'imagen' => 'fa fa-bar-chart-o',
            'etiqueta' => 'Estados de PQRS por Dependencia',
            'enlace' => $enlace,
            'cod_padre' => $cod_padre,
            'orden' => 5,
            'asignable' => 1,
            'tiene_hijos' => 0
        ]);
    }

    public function reporteCantidadPqrDependencia(){
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

    public function graficos(){
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

    public function downReporteDepencencia(){
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
    
    public function downReporteEstadosPqrDependencia(){
        $this->connection->delete('busqueda_componente', [
            'nombre' => 'rep_estado_depen_pqr'
        ]);

        $this->connection->delete('busqueda_condicion', [
            'etiqueta_condicion' => 'rep_estado_depen_pqr'
        ]);

        $this->connection->delete('modulo', [
            'nombre' => 'rep_estado_depen_pqr'
        ]);
    }

    public function downReporteCantidadPqrDependencia(){
        $this->connection->delete('busqueda_componente', [
            'nombre' => 'rep_total_pqr_depen'
        ]);

        $this->connection->delete('busqueda_condicion', [
            'etiqueta_condicion' => 'rep_total_pqr_depen'
        ]);
    }

    public function downGraficos(){
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
