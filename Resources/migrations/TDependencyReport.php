<?php

namespace App\Bundles\pqr\Resources\migrations;

use App\Bundles\pqr\Services\models\PqrForm;

trait TDependencyReport
{
    use TMigrations;

    protected function createComponentePorDependencia(int $idbusqueda)
    {
        $info = '[{"title":"DEPENDENCIA","field":"{*getDependencia@sys_dependencia*}","align":"center"},
                {"title":"CANTIDAD","field":"{*getCantidad@sys_dependencia*}","align":"center"},
                {"title":"PENDIENTES","field":"{*getPendientes@sys_dependencia*}","align":"center"},
                {"title":"RESUELTAS","field":"{*getResueltas@sys_dependencia*}","align":"center"}]';

        $nombreComponente = PqrForm::NOMBRE_REPORTE_POR_DEPENDENCIA;

        $dataComponente = [
            'busqueda_idbusqueda'    => $idbusqueda,
            'etiqueta'               => 'Por dependencia',
            'nombre'                 => $nombreComponente,
            'orden'                  => 5,
            'url'                    => 'views/buzones/grilla.php',
            'info'                   => $info,
            'campos_adicionales'     => 'v.sys_dependencia',
            'ordenado_por'           => 'v.sys_dependencia',
            'direccion'              => 'ASC',
            'agrupado_por'           => 'v.sys_dependencia',
            'llave'                  => 'v.sys_dependencia',
            'ruta_libreria'          => 'src/Bundles/pqr/formatos/pqr/reporteFunciones.php',
            'ruta_libreria_pantalla' => 'views/modules/pqr/formatos/pqr/reporteAcciones.js'
        ];

        $idbusquedaComponente = $this->createBusquedaComponente(
            $idbusqueda,
            $dataComponente,
            $nombreComponente
        );

        $busquedaCondicion = [
            'fk_busqueda_componente' => $idbusquedaComponente,
            'codigo_where'           => "(sys_dependencia <> '' AND sys_dependencia IS NOT NULL)",
            'etiqueta_condicion'     => $nombreComponente
        ];
        $this->createBusquedaCondicion($idbusquedaComponente, $busquedaCondicion, $nombreComponente);

        $this->createChildComponentePorDependencia($idbusqueda);
    }

    protected function createChildComponentePorDependencia(int $idbusqueda)
    {
        $info = '[{"title":"RADICADO","field":"{*verDocumento@iddocumento,numero*}","align":"center"},
                {"title":"FECHA","field":"{*dateRadication@fecha*}","align":"center"},
                {"title":"E-MAIL","field":"{*sys_email*}","align":"center"},
                {"title":"TIPO","field":"{*getValueSysTipo@iddocumento,sys_tipo*}","align":"center"},
                {"title":"ESTADO","field":"{*sys_estado*}","align":"center"}]';

        $nombreComponente = PqrForm::NOMBRE_REPORTE_REGISTROS_POR_DEPENDENCIA;

        $dataComponente = [
            'busqueda_idbusqueda'    => $idbusqueda,
            'etiqueta'               => 'Registros PQR',
            'nombre'                 => $nombreComponente,
            'orden'                  => 6,
            'url'                    => 'views/buzones/grilla.php',
            'info'                   => $info,
            'campos_adicionales'     => 'v.sys_estado, v.iddocumento, v.numero, v.fecha, v.sys_tipo, v.sys_email',
            'ordenado_por'           => 'v.numero',
            'direccion'              => 'DESC',
            'llave'                  => 'v.iddocumento',
            'ruta_libreria'          => 'src/Bundles/pqr/formatos/pqr/reporteFunciones.php,src/Bundles/pqr/formatos/reporteFuncionesGenerales.php',
            'ruta_libreria_pantalla' => 'views/modules/pqr/formatos/pqr/reporteAcciones.js'
        ];

        $idbusquedaComponente = $this->createBusquedaComponente(
            $idbusqueda,
            $dataComponente,
            $nombreComponente
        );

        $busquedaCondicion = [
            'fk_busqueda_componente' => $idbusquedaComponente,
            'codigo_where'           => "{*filter_pqr*}",
            'etiqueta_condicion'     => $nombreComponente
        ];
        $this->createBusquedaCondicion($idbusquedaComponente, $busquedaCondicion, $nombreComponente);

    }
}