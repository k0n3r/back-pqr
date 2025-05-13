<?php


namespace App\Bundles\pqr\Services\models;


use App\Bundles\pqr\Services\PqrResponseTimeService;
use Saia\core\model\Model;
use Saia\models\formatos\CampoOpciones;

class PqrResponseTime extends Model
{
    private ?CampoOpciones $CampoOpcion = null;

    protected function defineAttributes(): void
    {
        $this->dbAttributes = (object)[
            'safe'    => [
                'fk_campo_opciones',
                'fk_sys_tipo',
                'number_days',
                'active',
            ],
            'primary' => 'id',
            'table'   => 'pqr_response_times',
        ];
    }

    /**
     * Obtiene el servicio
     *
     * @return PqrResponseTimeService
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2021-06-06
     */
    public function getService(): PqrResponseTimeService
    {
        return new PqrResponseTimeService($this);
    }

    /**
     * Obtiene la instancia del campo segundario sobre el cual
     * se esta validando el tiempo de respuesta (opciones de sys_tipo)
     *
     * @return CampoOpciones
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2021-06-06
     */
    public function getCampoOpcionForSysTipo(): CampoOpciones
    {
        if (!$this->CampoOpcion) {
            $this->CampoOpcion = new CampoOpciones($this->fk_sys_tipo);
        }

        return $this->CampoOpcion;
    }

}