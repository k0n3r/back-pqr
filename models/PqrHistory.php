<?php

namespace Saia\Pqr\models;

use Saia\controllers\DateController;
use Saia\core\model\Model;

class PqrHistory extends Model
{
    use TModel;

    protected function defineAttributes(): void
    {
        $this->dbAttributes = (object) [
            'safe' => [
                'fecha',
                'nombre_funcionario',
                'descripcion',
                'idft'
            ],
            'data' => [
                'fecha'
            ],
            'primary' => 'id',
            'table' => 'pqr_history'
        ];
    }

    /**
     * Obtiene la fecha con el formato por defecto
     *
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function getFecha(): string
    {
        return DateController::convertDate($this->fecha);
    }
}
