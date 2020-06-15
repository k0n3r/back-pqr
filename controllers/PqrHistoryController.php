<?php

namespace Saia\Pqr\controllers;

use Saia\Pqr\models\PqrHistory;

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

        $rows = [];
        $records = PqrHistory::findAllByAttributes([
            'idft' => $this->request['idft']
        ], [], 'id desc');

        foreach ($records as $PqrHistory) {
            $rows[] = $PqrHistory->getDataAttributes();
        }

        return (object) [
            'total' => count($records),
            'rows' => $rows
        ];
    }
}
