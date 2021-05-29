<?php

namespace App\Bundles\pqr\Services\models;

use Saia\core\model\Model;
use Saia\models\Funcionario;
use Saia\controllers\DateController;
use App\Bundles\pqr\formatos\pqr_respuesta\FtPqrRespuesta;
use App\Bundles\pqr\Services\PqrHistoryService;

class PqrHistory extends Model
{
    use TModels;

    const TIPO_TAREA = 1;
    const TIPO_NOTIFICACION = 2;
    const TIPO_RESPUESTA = 3;
    const TIPO_CAMBIO_ESTADO = 4;
    const TIPO_CAMBIO_VENCIMIENTO = 5;
    const TIPO_CALIFICACION = 6;

    protected function defineAttributes(): void
    {
        $this->dbAttributes = (object)[
            'safe' => [
                'fecha',
                'fk_funcionario',
                'descripcion',
                'idft',
                'tipo',
                'idfk'
            ],
            'data' => [
                'fecha'
            ],
            'primary' => 'id',
            'table' => 'pqr_history'
        ];
    }

    /**
     * @return Funcionario
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2021-05-01
     */
    public function getFuncionario(): Funcionario
    {
        if (!$this->Funcionario) {
            $this->Funcionario = new Funcionario($this->fk_funcionario);
        }

        return $this->Funcionario;
    }

    /**
     * Retorna el servicio
     *
     * @return PqrHistoryService
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2021
     */
    public function getService(): PqrHistoryService
    {
        return new PqrHistoryService($this);
    }

    /**
     * Obtiene la fecha con el formato por defecto
     *
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function getFecha(): string
    {
        return DateController::convertDate($this->fecha);
    }

    /**
     * Obtiene la respuesta asociada
     *
     * @return FtPqrRespuesta|null
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function getRespuestaPqr(): ?FtPqrRespuesta
    {
        if (in_array($this->tipo, [
            self::TIPO_RESPUESTA,
            self::TIPO_CALIFICACION
        ])) {
            return new FtPqrRespuesta($this->idfk);
        }

        return null;
    }
}
