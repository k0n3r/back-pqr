<?php

namespace App\Bundles\pqr\Resources\migrations;

use App\Bundles\pqr\Services\models\PqrForm;

trait TDependencyReport
{
    use TMigrations;

    protected function createComponentePorDependencia(int $idbusqueda)
    {
        $info = '[{"title":"DEPENDENCIA","field":"{*getNombreDependencia@sys_dependencia*}","align":"center"},
                {"title":"CANTIDAD","field":"{*getCantidad@sys_dependencia*}","align":"center"},
                {"title":"PENDIENTES","field":"{*getPendientes@sys_dependencia*}","align":"center"},
                {"title":"RESUELTAS","field":"{*getResueltas@sys_dependencia*}","align":"center"}]';

        $nombreComponente = PqrForm::NOMBRE_REPORTE_POR_DEPENDENCIA;

        $dataComponente = [
            'busqueda_idbusqueda'    => $idbusqueda,
            'etiqueta'               => 'Por dependencia',
            'nombre'                 => $nombreComponente,
            'url'                    => 'views/buzones/grilla.php',
            'info'                   => $info,
            'busqueda_avanzada'      => 'views/modules/pqr/formatos/pqr/busqueda_dependencia.html',
            'ordenado_por'           => 'v.sys_dependencia ASC',
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
            'codigo_where'           => "sys_dependencia IS NOT NULL",
            'etiqueta_condicion'     => $nombreComponente
        ];
        $this->createBusquedaCondicion($idbusquedaComponente, $busquedaCondicion, $nombreComponente);
    }
}