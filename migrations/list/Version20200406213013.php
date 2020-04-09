<?php

declare(strict_types=1);

namespace Saia\Pqr\Migrations;

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
        return 'Creacion reporte de Respuesta a la PQR';
    }

    public function up(Schema $schema): void
    {
        $this->init();

        $busqueda = [
            'nombre' => 'reporte_respueta_pqr',
            'etiqueta' => 'Reporte de Respuesta a la PQR',
            'estado' => 1,
            'campos' => NULL,
            'tablas' => 'vpqr_respuesta v',
            'ruta_libreria' => 'app/modules/back_pqr/formatos/pqr_respuesta/reporteFunciones.php,app/modules/back_pqr/formatos/reporteFuncionesGenerales.php',
            'ruta_libreria_pantalla' => 'app/modules/back_pqr/formatos/pqr_respuesta/reporteAcciones.php',
            'cantidad_registros' => 20,
            'tipo_busqueda' => 2
        ];
        $idbusqueda = $this->createBusqueda($busqueda, 'reporte_respueta_pqr');

        $nombreComponente = 'respuesta_pqr';
        $busquedaComponente = [
            'busqueda_idbusqueda' => $idbusqueda,
            'etiqueta' => 'Respuesta PQR',
            'nombre' => $nombreComponente,
            'orden' => 1,
            'url' => NULL,
            'info' => '[{"title":"RADICADO","field":"{*view@iddocumento,numero*}","align":"center"},{"title":"FECHA","field":"{*dateRadication@fecha*}","align":"center"},{"title":"RESPONSABLE","field":"{*getResponsable@iddocumento*}","align":"center"},{"title":"CALIFICACIÓN","field":"{*viewCalificacion@idft*}","align":"center"}]',
            'encabezado_componente' => NULL,
            'campos_adicionales' => 'v.numero,v.fecha,v.idft',
            'tablas_adicionales' => NULL,
            'ordenado_por' => 'v.fecha',
            'direccion' => 'DESC',
            'agrupado_por' => NULL,
            'busqueda_avanzada' => NULL,
            'enlace_adicionar' => NULL,
            'llave' => 'v.iddocumento'
        ];
        $idbusquedaComponente = $this->createBusquedaComponente($idbusqueda, $busquedaComponente, $nombreComponente);

        $busquedaCondicion = [
            'fk_busqueda_componente' => $idbusquedaComponente,
            'codigo_where' => "{*filter_answer_by_pqr*}",
            'etiqueta_condicion' => $nombreComponente
        ];
        $this->createBusquedaCondicion($idbusquedaComponente, $busquedaCondicion, $nombreComponente);
    }

    public function down(Schema $schema): void
    {
        $this->deleteBusqueda('reporte_respueta_pqr');
    }
}
