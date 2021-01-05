<?php

namespace App\Bundles\pqr\Services\controllers;

use App\Bundles\pqr\formatos\pqr\FtPqr;

class PqrHistoryController extends Controller
{

    /**
     * Obtiene listado de Historial de cambios
     *
     * @return object
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function getHistory(): object
    {
        $Response = (object) [
            'total' => 0,
            'rows' => []
        ];

        if (!$this->request['idft']) {
            return $Response;
        }
        $FtPqr = new FtPqr($this->request['idft']);
        $rows = [];
        $records = $FtPqr->getHistory();

        foreach ($records as $PqrHistory) {
            $rows[] = array_merge(
                $PqrHistory->getDataAttributes(),
                [
                    'nombre_funcionario' => $PqrHistory->Funcionario->getName()
                ]
            );
        }

        return (object) [
            'total' => count($records),
            'rows' => $rows
        ];
    }
}
