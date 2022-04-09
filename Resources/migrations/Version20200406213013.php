<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Resources\migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200406213013 extends AbstractMigration
{
    use TMigrations;

    public function getDescription(): string
    {
        return 'Creacion reporte de Respuesta a la PQR y Calificación PQR';
    }

    public function up(Schema $schema): void
    {
        $this->init();

        $busqueda = [
            'nombre'             => 'rep_respuesta_calificacion_pqr',
            'etiqueta'           => 'Reporte de Respuesta y Calificación a la PQRSF',
            'estado'             => 1,
            'campos'             => null,
            'tablas'             => null,
            'cantidad_registros' => 20,
            'tipo_busqueda'      => 2
        ];
        $idbusqueda = $this->createBusqueda($busqueda, 'rep_respuesta_calificacion_pqr');

        $this->reporteRespuestaPqr($idbusqueda);
        $this->reporteCalificacionPqr($idbusqueda);
    }

    private function reporteRespuestaPqr(int $idbusqueda)
    {

        $nombreComponente = 'respuesta_pqr';
        $busquedaComponente = [
            'busqueda_idbusqueda'    => $idbusqueda,
            'etiqueta'               => 'Respuestas PQRSF',
            'nombre'                 => $nombreComponente,
            'orden'                  => 1,
            'url'                    => 'views/buzones/grilla.php',
            'info'                   => '[{"title":"RADICADO","field":"{*view@iddocumento,numero*}","align":"center"},{"title":"FECHA","field":"{*dateRadication@fecha*}","align":"center"},{"title":"RESPONSABLE","field":"{*getResponsable@iddocumento*}","align":"center"},{"title":"CALIFICACIÓN","field":"{*viewCalificacion@idft*}","align":"center"}]',
            'encabezado_componente'  => null,
            'campos_adicionales'     => 'v.numero,v.fecha,v.idft',
            'tablas_adicionales'     => 'vpqr_respuesta v',
            'ordenado_por'           => 'v.fecha',
            'direccion'              => 'DESC',
            'agrupado_por'           => null,
            'busqueda_avanzada'      => null,
            'enlace_adicionar'       => null,
            'llave'                  => 'v.iddocumento',
            'ruta_libreria'          => 'src/Bundles/pqr/formatos/pqr_respuesta/reporteFunciones.php,src/Bundles/pqr/formatos/reporteFuncionesGenerales.php',
            'ruta_libreria_pantalla' => 'views/modules/pqr/formatos/pqr_respuesta/reporteAcciones.js',
        ];
        $idbusquedaComponente = $this->createBusquedaComponente($idbusqueda, $busquedaComponente, $nombreComponente);

        $busquedaCondicion = [
            'fk_busqueda_componente' => $idbusquedaComponente,
            'codigo_where'           => "{*filter_answer_by_pqr*}",
            'etiqueta_condicion'     => $nombreComponente
        ];
        $this->createBusquedaCondicion($idbusquedaComponente, $busquedaCondicion, $nombreComponente);
    }

    private function reporteCalificacionPqr(int $idbusqueda)
    {

        $nombreComponente = 'calificacion_pqr';
        $busquedaComponente = [
            'busqueda_idbusqueda'    => $idbusqueda,
            'etiqueta'               => 'Calificación PQRSF',
            'nombre'                 => $nombreComponente,
            'orden'                  => 2,
            'url'                    => 'views/buzones/grilla.php',
            'info'                   => '[{"title":"RADICADO","field":"{*view@iddocumento,numero*}","align":"center"},{"title":"FECHA","field":"{*dateRadication@fecha*}","align":"center"},{"title":"GESTIÓN","field":"{*getGestion@iddocumento,experiencia_gestion*}","align":"center"},{"title":"SERVICIO","field":"{*getServicio@iddocumento,experiencia_servicio*}","align":"center"}]',
            'encabezado_componente'  => null,
            'campos_adicionales'     => 'v.numero,v.fecha,v.idft,v.experiencia_gestion,v.experiencia_servicio',
            'tablas_adicionales'     => 'vpqr_calificacion v',
            'ordenado_por'           => 'v.fecha',
            'direccion'              => 'DESC',
            'agrupado_por'           => null,
            'busqueda_avanzada'      => null,
            'enlace_adicionar'       => null,
            'llave'                  => 'v.iddocumento',
            'ruta_libreria'          => 'src/Bundles/pqr/formatos/pqr_calificacion/reporteFunciones.php,src/Bundles/pqr/formatos/reporteFuncionesGenerales.php',
            'ruta_libreria_pantalla' => null,
        ];
        $idbusquedaComponente = $this->createBusquedaComponente($idbusqueda, $busquedaComponente, $nombreComponente);

        $busquedaCondicion = [
            'fk_busqueda_componente' => $idbusquedaComponente,
            'codigo_where'           => "{*filter_calificacion*}",
            'etiqueta_condicion'     => $nombreComponente
        ];
        $this->createBusquedaCondicion($idbusquedaComponente, $busquedaCondicion, $nombreComponente);
    }

    public function down(Schema $schema): void
    {
        $this->deleteBusqueda('rep_respuesta_calificacion_pqr');
    }
}
