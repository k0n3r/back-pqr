<?php

declare(strict_types=1);

namespace Saia\Pqr\migrations;

use Doctrine\DBAL\Schema\Schema;
use Saia\Pqr\formatos\pqr\FtPqr;
use Saia\Pqr\migrations\TMigrations;
use Doctrine\Migrations\AbstractMigration;
use Saia\Pqr\models\PqrForm;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200226192642 extends AbstractMigration
{
    use TMigrations;

    public function getDescription(): string
    {
        return 'Creacion de los reportes pendiente, proceso y terminado';
    }

    public function up(Schema $schema): void
    {
        $this->init();

        $busqueda = [
            'nombre' => 'reporte_pqr',
            'etiqueta' => 'Reporte de PQRSF',
            'estado' => 1,
            'campos' => NULL,
            'tablas' => 'vpqr v',
            'ruta_libreria' => 'app/modules/back_pqr/formatos/pqr/reporteFunciones.php,app/modules/back_pqr/formatos/reporteFuncionesGenerales.php',
            'ruta_libreria_pantalla' => 'app/modules/back_pqr/formatos/pqr/reporteAcciones.php',
            'cantidad_registros' => 20,
            'tipo_busqueda' => 2
        ];
        $idbusqueda = $this->createBusqueda($busqueda, 'reporte_pqr');

        $this->createComponentePendientes($idbusqueda);
        $this->createComponenteProcesos($idbusqueda);
        $this->createComponenteTerminados($idbusqueda);
    }

    protected function getDefaultData(bool $ViewNewField = false)
    {

        $NewField = $ViewNewField ? '{"title":"TAREAS","field":"{*totalTask@iddocumento*}","align":"center"},{"title":"RESPUESTAS","field":"{*totalAnswers@idft*}","align":"center"},' : '';

        return [
            'url' => NULL,
            'info' => '[{"title":"RADICADO","field":"{*viewFtPqr@idft,numero*}","align":"center"},{"title":"FECHA","field":"{*dateRadication@fecha*}","align":"center"},{"title":"E-MAIL","field":"{*sys_email*}","align":"center"},{"title":"TIPO","field":"{*getValueSysTipo@iddocumento,sys_tipo*}","align":"center"},{"title":"VENCIMIENTO","field":"{*getExpiration@idft*}","align":"center"},' . $NewField . '{"title":"OPCIONES","field":"{*options@iddocumento,sys_estado,idft*}","align":"center"}]',
            'encabezado_componente' => NULL,
            'campos_adicionales' => 'v.numero,v.fecha,v.sys_email,v.sys_tipo,v.sys_estado,v.idft',
            'tablas_adicionales' => NULL,
            'ordenado_por' => 'v.fecha',
            'direccion' => 'DESC',
            'agrupado_por' => NULL,
            'busqueda_avanzada' => 'app/modules/back_pqr/formatos/pqr/busqueda.php',
            'enlace_adicionar' => NULL,
            'llave' => 'v.iddocumento'
        ];
    }

    protected function createComponentePendientes(int $idbusqueda)
    {
        $nombreComponente = PqrForm::NOMBRE_REPORTE_PENDIENTE;

        $dataComponente = [
            'busqueda_idbusqueda' => $idbusqueda,
            'etiqueta' => 'Pendientes',
            'nombre' => $nombreComponente,
            'orden' => 1
        ];
        $busquedaComponente = array_merge($dataComponente, $this->getDefaultData());

        $idbusquedaComponente = $this->createBusquedaComponente(
            $idbusqueda,
            $busquedaComponente,
            $nombreComponente
        );

        $estado = FtPqr::ESTADO_PENDIENTE;
        $busquedaCondicion = [
            'fk_busqueda_componente' => $idbusquedaComponente,
            'codigo_where' => "sys_estado='{$estado}'",
            'etiqueta_condicion' => $nombreComponente
        ];
        $this->createBusquedaCondicion($idbusquedaComponente, $busquedaCondicion, $nombreComponente);

        $data = [
            'enlace' => 'views/dashboard/kaiten_dashboard.php?panels=[{"kConnector": "iframe","url": "views/buzones/grilla.php?idbusqueda_componente=' . $idbusquedaComponente . '"}]',
        ];
        $this->createModulo($data, $nombreComponente);
    }

    protected function createComponenteProcesos(int $idbusqueda)
    {
        $nombreComponente = PqrForm::NOMBRE_REPORTE_PROCESO;

        $dataComponente = [
            'busqueda_idbusqueda' => $idbusqueda,
            'etiqueta' => 'En proceso',
            'nombre' => $nombreComponente,
            'orden' => 2
        ];
        $busquedaComponente = array_merge($dataComponente, $this->getDefaultData(true));

        $idbusquedaComponente = $this->createBusquedaComponente(
            $idbusqueda,
            $busquedaComponente,
            $nombreComponente
        );

        $estado = FtPqr::ESTADO_PROCESO;
        $busquedaCondicion = [
            'fk_busqueda_componente' => $idbusquedaComponente,
            'codigo_where' => "sys_estado='{$estado}'",
            'etiqueta_condicion' => $nombreComponente
        ];
        $this->createBusquedaCondicion($idbusquedaComponente, $busquedaCondicion, $nombreComponente);

        $data = [
            'enlace' => 'views/dashboard/kaiten_dashboard.php?panels=[{"kConnector": "iframe","url": "views/buzones/grilla.php?idbusqueda_componente=' . $idbusquedaComponente . '"}]',
        ];
        $this->createModulo($data, $nombreComponente);
    }

    protected function createComponenteTerminados(int $idbusqueda)
    {
        $nombreComponente = PqrForm::NOMBRE_REPORTE_TERMINADO;
        $dataComponente = [
            'busqueda_idbusqueda' => $idbusqueda,
            'etiqueta' => 'Terminados',
            'nombre' => $nombreComponente,
            'orden' => 3
        ];
        //'acciones_seleccionados' => 'answers'
        $busquedaComponente = array_merge($dataComponente, $this->getDefaultData(true));

        $idbusquedaComponente = $this->createBusquedaComponente(
            $idbusqueda,
            $busquedaComponente,
            $nombreComponente
        );

        $estado = FtPqr::ESTADO_TERMINADO;
        $busquedaCondicion = [
            'fk_busqueda_componente' => $idbusquedaComponente,
            'codigo_where' => "sys_estado='{$estado}'",
            'etiqueta_condicion' => $nombreComponente
        ];
        $this->createBusquedaCondicion($idbusquedaComponente, $busquedaCondicion, $nombreComponente);

        $data = [
            'enlace' => 'views/dashboard/kaiten_dashboard.php?panels=[{"kConnector": "iframe","url": "views/buzones/grilla.php?idbusqueda_componente=' . $idbusquedaComponente . '"}]',
        ];
        $this->createModulo($data, $nombreComponente);
    }

    public function down(Schema $schema): void
    {
        $this->deleteBusqueda('reporte_pqr');
    }
}
