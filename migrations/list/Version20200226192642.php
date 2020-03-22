<?php

declare(strict_types=1);

namespace Saia\Pqr\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Saia\Pqr\formatos\pqr\FtPqr;
use Saia\Pqr\Migrations\TMigrations;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200226192642 extends AbstractMigration
{
    use TMigrations;

    public function getDescription(): string
    {
        return 'Creacion de los reportes (Busquedas)';
    }

    public function up(Schema $schema): void
    {
        $this->init();

        $busqueda = [
            'nombre' => 'reporte_pqr',
            'etiqueta' => 'Reporte de PQR',
            'estado' => 1,
            'campos' => NULL,
            'tablas' => 'vpqr v',
            'ruta_libreria' => 'app/modules/back_pqr/formatos/pqr/reporteFunciones.php',
            'ruta_libreria_pantalla' => 'app/modules/back_pqr/formatos/pqr/reporteAcciones.php,app/modules/back_pqr/formatos/pqr/accionesSeleccionados.php',
            'cantidad_registros' => 20,
            'tipo_busqueda' => 2
        ];
        $idbusqueda = $this->createBusqueda($busqueda, 'reporte_pqr');

        $sql = "SELECT idmodulo FROM modulo WHERE lower(nombre) like '{$this->getNameMainModule()}'";
        $modulo = $this->connection->fetchAll($sql);

        if (!$modulo[0]['idmodulo']) {
            $this->abortIf(true, "NO se encontro el agrupador principal del modulo");
        }

        $data = [
            'pertenece_nucleo' => 0,
            'nombre' => 'reporte_pqr',
            'tipo' => 1,
            'imagen' => 'fa fa-bar-chart-o',
            'etiqueta' => 'Reportes',
            'enlace' => NULL,
            'cod_padre' => $modulo[0]['idmodulo'],
            'orden' => 2
        ];
        $idmodulo = $this->createModulo($data, 'reporte_pqr');

        $this->createComponentePendientes($idbusqueda, $idmodulo);
        $this->createComponenteProcesos($idbusqueda, $idmodulo);
        $this->createComponenteTerminados($idbusqueda, $idmodulo);
    }


    protected function createBusqueda(array $data, string $search): int
    {
        $sql = "SELECT idbusqueda FROM busqueda WHERE lower(nombre) like '{$search}'";
        $record = $this->connection->fetchAll($sql);

        if ($record[0]['idbusqueda']) {
            $id = $record[0]['idbusqueda'];
            $this->connection->update('busqueda', $data, [
                'idbusqueda' => $id
            ]);
        } else {
            $this->connection->insert('busqueda', $data);
            $id = $this->connection->lastInsertId();
        }

        return (int) $id;
    }

    protected function getDefaultData(bool $ViewNewField = false)
    {

        $NewField = $ViewNewField ? '{"title":"TAREAS","field":"{*totalTask@iddocumento*}","align":"center"},' : '';

        return [
            'url' => NULL,
            'info' => '[{"title":"RADICADO","field":"{*view@iddocumento,numero*}","align":"center"},{"title":"FECHA","field":"{*dateRadication@fecha*}","align":"center"},{"title":"TIPO","field":"{*sys_tipo*}","align":"center"},{"title":"E-MAIL","field":"{*sys_email*}","align":"center"},' . $NewField . '{"title":"OPCIONES","field":"{*options@iddocumento,sys_estado*}","align":"center"}]',
            'encabezado_componente' => NULL,
            'campos_adicionales' => 'v.numero,v.fecha,v.sys_email,v.sys_tipo,v.sys_estado',
            'tablas_adicionales' => NULL,
            'ordenado_por' => 'v.fecha',
            'direccion' => 'DESC',
            'agrupado_por' => NULL,
            'busqueda_avanzada' => 'app/modules/back_pqr/formatos/pqr/busqueda.php',
            'enlace_adicionar' => NULL,
            'llave' => 'v.iddocumento'
        ];
    }

    protected function createComponentePendientes(int $idbusqueda, int $idmodulo)
    {
        $nombreComponente = 'pendientes_pqr';
        $dataComponente = [
            'busqueda_idbusqueda' => $idbusqueda,
            'etiqueta' => 'Pendientes',
            'nombre' => $nombreComponente,
            'orden' => 1
        ];
        $busquedaComponente = array_merge($dataComponente, $this->getDefaultData());

        $idbusquedaComponente = $this->createBusquedaComponente($idbusqueda, $busquedaComponente, $nombreComponente);

        $estado = FtPqr::ESTADO_PENDIENTE;
        $busquedaCondicion = [
            'fk_busqueda_componente' => $idbusquedaComponente,
            'codigo_where' => "sys_estado='{$estado}'",
            'etiqueta_condicion' => $nombreComponente
        ];
        $this->createBusquedaCondicion($idbusquedaComponente, $busquedaCondicion, $nombreComponente);

        $data = [
            'pertenece_nucleo' => 0,
            'nombre' => $nombreComponente,
            'tipo' => 2,
            'imagen' => 'fa fa-bar-chart-o',
            'etiqueta' => 'Pendientes',
            'enlace' => 'views/dashboard/kaiten_dashboard.php?panels=[{"kConnector": "iframe","url": "views/buzones/grilla.php?idbusqueda_componente=' . $idbusquedaComponente . '"}]',
            'cod_padre' => $idmodulo,
            'orden' => 1
        ];
        $this->createModulo($data, $nombreComponente);
    }

    protected function createComponenteProcesos(int $idbusqueda, int $idmodulo)
    {
        $nombreComponente = 'proceso_pqr';
        $dataComponente = [
            'busqueda_idbusqueda' => $idbusqueda,
            'etiqueta' => 'En proceso',
            'nombre' => $nombreComponente,
            'orden' => 2
        ];
        $busquedaComponente = array_merge($dataComponente, $this->getDefaultData(true));

        $idbusquedaComponente = $this->createBusquedaComponente($idbusqueda, $busquedaComponente, $nombreComponente);

        $estado = FtPqr::ESTADO_PROCESO;
        $busquedaCondicion = [
            'fk_busqueda_componente' => $idbusquedaComponente,
            'codigo_where' => "sys_estado='{$estado}'",
            'etiqueta_condicion' => $nombreComponente
        ];
        $this->createBusquedaCondicion($idbusquedaComponente, $busquedaCondicion, $nombreComponente);

        $data = [
            'pertenece_nucleo' => 0,
            'nombre' => $nombreComponente,
            'tipo' => 2,
            'imagen' => 'fa fa-bar-chart-o',
            'etiqueta' => 'En proceso',
            'enlace' => 'views/dashboard/kaiten_dashboard.php?panels=[{"kConnector": "iframe","url": "views/buzones/grilla.php?idbusqueda_componente=' . $idbusquedaComponente . '"}]',
            'cod_padre' => $idmodulo,
            'orden' => 2
        ];
        $this->createModulo($data, $nombreComponente);
    }

    protected function createComponenteTerminados(int $idbusqueda, int $idmodulo)
    {
        $nombreComponente = 'terminados_pqr';
        $dataComponente = [
            'busqueda_idbusqueda' => $idbusqueda,
            'etiqueta' => 'Terminados',
            'nombre' => $nombreComponente,
            'orden' => 3,
            'acciones_seleccionados' => 'answers'
        ];
        $busquedaComponente = array_merge($dataComponente, $this->getDefaultData(true));

        $idbusquedaComponente = $this->createBusquedaComponente($idbusqueda, $busquedaComponente, $nombreComponente);

        $estado = FtPqr::ESTADO_TERMINADO;
        $busquedaCondicion = [
            'fk_busqueda_componente' => $idbusquedaComponente,
            'codigo_where' => "sys_estado='{$estado}'",
            'etiqueta_condicion' => $nombreComponente
        ];
        $this->createBusquedaCondicion($idbusquedaComponente, $busquedaCondicion, $nombreComponente);

        $data = [
            'pertenece_nucleo' => 0,
            'nombre' => $nombreComponente,
            'tipo' => 2,
            'imagen' => 'fa fa-bar-chart-o',
            'etiqueta' => 'Terminados',
            'enlace' => 'views/dashboard/kaiten_dashboard.php?panels=[{"kConnector": "iframe","url": "views/buzones/grilla.php?idbusqueda_componente=' . $idbusquedaComponente . '"}]',
            'cod_padre' => $idmodulo,
            'orden' => 3
        ];
        $this->createModulo($data, $nombreComponente);
    }

    protected function createBusquedaComponente(int $idbusqueda, array $data, string $search): int
    {
        $sql = "SELECT idbusqueda_componente FROM busqueda_componente
        WHERE busqueda_idbusqueda={$idbusqueda} AND lower(nombre) like '{$search}'";
        $record = $this->connection->fetchAll($sql);

        if ($record[0]['idbusqueda_componente']) {
            $id = $record[0]['idbusqueda_componente'];

            $this->connection->update('busqueda_componente', $data, [
                'idbusqueda_componente' => $id
            ]);
        } else {
            $this->connection->insert('busqueda_componente', $data);
            $id = $this->connection->lastInsertId();
        }

        return (int) $id;
    }

    protected function createBusquedaCondicion(int $idbusquedaComponente, array $data, string $search): int
    {
        $sql = "SELECT idbusqueda_condicion FROM busqueda_condicion
        WHERE fk_busqueda_componente={$idbusquedaComponente} AND lower(etiqueta_condicion) like '{$search}'";
        $record = $this->connection->fetchAll($sql);

        if ($record[0]['idbusqueda_condicion']) {
            $id = $record[0]['idbusqueda_condicion'];
            $this->connection->update('busqueda_condicion', $data, [
                'idbusqueda_condicion' => $id
            ]);
        } else {
            $this->connection->insert('busqueda_condicion', $data);
            $id = $this->connection->lastInsertId();
        }

        return (int) $id;
    }

    public function down(Schema $schema): void
    {
        $this->deleteModulo('reporte_pqr');
        $this->deleteModulo('pendientes_pqr');
        $this->deleteModulo('proceso_pqr');
        $this->deleteModulo('terminados_pqr');

        $this->deleteBusqueda('reporte_pqr');
    }

    protected function deleteBusqueda(string $search): void
    {
        $sql = "SELECT idbusqueda FROM busqueda WHERE lower(nombre) like '{$search}'";
        $busqueda = $this->connection->fetchAll($sql);

        if ($busqueda[0]['idbusqueda']) {
            $idbusqueda = $busqueda[0]['idbusqueda'];
            $this->connection->delete('busqueda', [
                'idbusqueda' => $idbusqueda
            ]);

            $sql = "SELECT idbusqueda_componente FROM busqueda_componente WHERE busqueda_idbusqueda={$idbusqueda}";
            $records = $this->connection->fetchAll($sql);

            foreach ($records as $busquedaComponente) {
                $idbusquedaComponente = $busquedaComponente['idbusqueda_componente'];
                $this->connection->delete('busqueda_componente', [
                    'idbusqueda_componente' => $idbusquedaComponente
                ]);

                $this->connection->delete('busqueda_condicion', [
                    'fk_busqueda_componente' => $idbusquedaComponente
                ]);
            }
        }
    }
}
