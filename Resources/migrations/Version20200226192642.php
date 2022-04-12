<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Resources\migrations;

use App\Bundles\pqr\Services\models\PqrForm;
use Doctrine\DBAL\Schema\Schema;
use App\Bundles\pqr\formatos\pqr\FtPqr;
use Doctrine\Migrations\AbstractMigration;


/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200226192642 extends AbstractMigration
{
    use TDependencyReport;

    public function getDescription(): string
    {
        return 'Creación de los reportes';
    }

    public function up(Schema $schema): void
    {
        $this->init();

        $busqueda = [
            'nombre'             => 'reporte_pqr',
            'etiqueta'           => 'Reporte de PQRSF',
            'estado'             => 1,
            'campos'             => null,
            'tablas'             => 'vpqr v',
            'cantidad_registros' => 20,
            'tipo_busqueda'      => 2
        ];
        $idbusqueda = $this->createBusqueda($busqueda, 'reporte_pqr');

        $this->createComponentePendientes($idbusqueda);
        $this->createComponenteProcesos($idbusqueda);
        $this->createComponenteTerminados($idbusqueda);
        $this->createComponenteTodos($idbusqueda);
        $this->createComponentePorDependencia($idbusqueda);
    }

    private function getDefaultData(string $nameReport): array
    {
        switch ($nameReport) {
            case PqrForm::NOMBRE_REPORTE_TODOS:
                $NewField = '{"title":"ESTADO","field":"{*sys_estado*}","align":"center"},{"title":"VENCIMIENTO","field":"{*getExpiration@idft*}","align":"center"},{"title":"TAREAS","field":"{*totalTask@iddocumento*}","align":"center"},{"title":"RESPUESTAS","field":"{*totalAnswers@idft*}","align":"center"},';
                break;
            case PqrForm::NOMBRE_REPORTE_PROCESO:
                $NewField = '{"title":"VENCIMIENTO","field":"{*getExpiration@idft*}","align":"center"},{"title":"TAREAS","field":"{*totalTask@iddocumento*}","align":"center"},{"title":"RESPUESTAS","field":"{*totalAnswers@idft*}","align":"center"},';
                break;
            case PqrForm::NOMBRE_REPORTE_TERMINADO:
                $NewField = '{"title":"FECHA FINALIZACIÓN","field":"{*getEndDate@idft*}","align":"center"},{"title":"DÍAS RETRASO","field":"{*getDaysLate@idft*}","align":"center"},{"title":"TAREAS","field":"{*totalTask@iddocumento*}","align":"center"},{"title":"RESPUESTAS","field":"{*totalAnswers@idft*}","align":"center"},';
                break;
            case PqrForm::NOMBRE_REPORTE_PENDIENTE:
            default:
                $NewField = '{"title":"VENCIMIENTO","field":"{*getExpiration@idft*}","align":"center"},';
                break;
        }

        return [
            'url'                    => 'views/buzones/grilla.php',
            'info'                   => '[{"title":"RADICADO","field":"{*viewFtPqr@idft,numero*}","align":"center"},{"title":"FECHA","field":"{*dateRadication@fecha*}","align":"center"},{"title":"E-MAIL","field":"{*sys_email*}","align":"center"},{"title":"TIPO","field":"{*getValueSysTipo@iddocumento,sys_tipo*}","align":"center"},' . $NewField . '{"title":"OPCIONES","field":"{*options@iddocumento,sys_estado,idft*}","align":"center"}]',
            'encabezado_componente'  => null,
            'campos_adicionales'     => 'v.numero,v.fecha,v.sys_email,v.sys_tipo,v.sys_estado,v.idft',
            'tablas_adicionales'     => null,
            'ordenado_por'           => 'v.fecha',
            'direccion'              => 'ASC',
            'agrupado_por'           => null,
            'busqueda_avanzada'      => 'views/modules/pqr/formatos/pqr/busqueda.php',
            'enlace_adicionar'       => null,
            'llave'                  => 'v.iddocumento',
            'ruta_libreria'          => 'src/Bundles/pqr/formatos/pqr/reporteFunciones.php',
            'ruta_libreria_pantalla' => 'views/modules/pqr/formatos/pqr/reporteAcciones.js',
        ];
    }

    private function createComponentePendientes(int $idbusqueda)
    {
        $nombreComponente = PqrForm::NOMBRE_REPORTE_PENDIENTE;
        $estado = FtPqr::ESTADO_PENDIENTE;

        $dataComponente = [
            'busqueda_idbusqueda' => $idbusqueda,
            'etiqueta'            => 'Pendientes',
            'nombre'              => $nombreComponente,
            'orden'               => 1
        ];

        $this->createComponent($idbusqueda, $dataComponente, $nombreComponente, $estado);
    }

    private function createComponenteProcesos(int $idbusqueda)
    {
        $nombreComponente = PqrForm::NOMBRE_REPORTE_PROCESO;

        $dataComponente = [
            'busqueda_idbusqueda' => $idbusqueda,
            'etiqueta'            => 'En proceso',
            'nombre'              => $nombreComponente,
            'orden'               => 2
        ];
        $busquedaComponente = array_merge($dataComponente, $this->getDefaultData($nombreComponente));

        $idbusquedaComponente = $this->createBusquedaComponente(
            $idbusqueda,
            $busquedaComponente,
            $nombreComponente
        );

        $estado = FtPqr::ESTADO_PROCESO;
        $busquedaCondicion = [
            'fk_busqueda_componente' => $idbusquedaComponente,
            'codigo_where'           => "sys_estado='$estado'",
            'etiqueta_condicion'     => $nombreComponente
        ];
        $this->createBusquedaCondicion($idbusquedaComponente, $busquedaCondicion, $nombreComponente);

        $data = [
            'enlace' => 'views/dashboard/kaiten_dashboard.php?panels=[{"kConnector": "iframe","url": "views/buzones/grilla.php?idbusqueda_componente=' . $idbusquedaComponente . '"}]',
        ];
        $this->createModulo($data, $nombreComponente);
    }

    private function createComponenteTerminados(int $idbusqueda)
    {
        $nombreComponente = PqrForm::NOMBRE_REPORTE_TERMINADO;
        $estado = FtPqr::ESTADO_TERMINADO;

        $dataComponente = [
            'busqueda_idbusqueda' => $idbusqueda,
            'etiqueta'            => 'Terminados',
            'nombre'              => $nombreComponente,
            'orden'               => 3
        ];

        $this->createComponent($idbusqueda, $dataComponente, $nombreComponente, $estado);
    }

    private function createComponent($idbusqueda, $dataComponente, $nombreComponente, $estado)
    {
        $idbusquedaComponente = $this->createBusquedaComponente(
            $idbusqueda,
            array_merge($dataComponente, $this->getDefaultData($nombreComponente)),
            $nombreComponente
        );

        $busquedaCondicion = [
            'fk_busqueda_componente' => $idbusquedaComponente,
            'codigo_where'           => "sys_estado='$estado'",
            'etiqueta_condicion'     => $nombreComponente
        ];
        $this->createBusquedaCondicion($idbusquedaComponente, $busquedaCondicion, $nombreComponente);

        $data = [
            'enlace' => 'views/dashboard/kaiten_dashboard.php?panels=[{"kConnector": "iframe","url": "views/buzones/grilla.php?idbusqueda_componente=' . $idbusquedaComponente . '"}]',
        ];
        $this->createModulo($data, $nombreComponente);
    }

    private function createComponenteTodos(int $idbusqueda)
    {
        $nombreComponente = PqrForm::NOMBRE_REPORTE_TODOS;
        $dataComponente = [
            'busqueda_idbusqueda' => $idbusqueda,
            'etiqueta'            => 'Todas',
            'nombre'              => $nombreComponente,
            'orden'               => 4
        ];
        $busquedaComponente = array_merge($dataComponente, $this->getDefaultData($nombreComponente));

        $idbusquedaComponente = $this->createBusquedaComponente(
            $idbusqueda,
            $busquedaComponente,
            $nombreComponente
        );

        $busquedaCondicion = [
            'fk_busqueda_componente' => $idbusquedaComponente,
            'codigo_where'           => "{*filter_pqr*}",
            'etiqueta_condicion'     => $nombreComponente
        ];
        $this->createBusquedaCondicion($idbusquedaComponente, $busquedaCondicion, $nombreComponente);

        $nombre = PqrForm::NOMBRE_PANTALLA_GRAFICO;
        $sql = "SELECT idpantalla_grafico FROM pantalla_grafico WHERE lower(nombre) like lower('$nombre')";
        $idPantallaGrafico = (int)$this->connection->fetchOne($sql);

        $this->abortIf(!$idPantallaGrafico, "No se encuentra la pantalla del grafico");

        $data = [
            'enlace' => 'views/dashboard/kaiten_dashboard.php?panels=[{"kConnector": "iframe","url": "views/buzones/listado_componentes.php?searchId=' . $idbusqueda . '"},{"kConnector": "iframe","url": "views/graficos/dashboard.php?screen=' . $idPantallaGrafico . '","kTitle": "Indicadores"}]',
        ];
        $this->createModulo($data, 'indicadores_pqr');
    }

    public function down(Schema $schema): void
    {
        $this->deleteBusqueda('reporte_pqr');
    }
}
