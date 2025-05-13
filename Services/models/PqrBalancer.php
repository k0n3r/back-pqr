<?php

namespace App\Bundles\pqr\Services\models;

use App\Bundles\pqr\Services\PqrBalancerService;
use Saia\core\model\Model;
use Saia\models\formatos\CampoOpciones;
use Saia\models\grupo\Grupo;

class PqrBalancer extends Model
{
    private ?CampoOpciones $CampoOpcion = null;
    private ?Grupo $Grupo = null;

    protected function defineAttributes(): void
    {
        $this->dbAttributes = (object)[
            'safe'    => [
                'fk_campo_opciones',
                'fk_sys_tipo',
                'fk_grupo',
                'active',
            ],
            'primary' => 'id',
            'table'   => 'pqr_balancer',
        ];
    }

    public function getService(): PqrBalancerService
    {
        return new PqrBalancerService($this);
    }


    /**
     * Obtiene la instancia del campo segundario sobre el cual
     * se esta validando el balanceo (opciones de sys_tipo)
     *
     * @return CampoOpciones
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2024-02-17
     */
    public function getCampoOpcionForSysTipo(): CampoOpciones
    {
        if (!$this->CampoOpcion) {
            $this->CampoOpcion = new CampoOpciones($this->fk_sys_tipo);
        }

        return $this->CampoOpcion;
    }

    /**
     * @return null|Grupo
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2024-02-20
     */
    public function getGrupo(): ?Grupo
    {
        if (!$this->Grupo && $this->fk_grupo > 0) {
            $this->Grupo = new Grupo($this->fk_grupo);
        }

        return $this->Grupo;
    }
}