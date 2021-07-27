<?php

namespace App\Bundles\pqr\Services\models;

use App\Bundles\pqr\Services\PqrNotificationService;
use Saia\core\model\Model;
use Saia\models\Funcionario;

class PqrNotification extends Model
{
    use TModels;

    private ?Funcionario $Funcionario = null;

    protected function defineAttributes(): void
    {
        $this->dbAttributes = (object)[
            'safe'    => [
                'fk_funcionario',
                'fk_pqr_form',
                'email',
                'notify'
            ],
            'primary' => 'id',
            'table'   => 'pqr_notifications'
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
     * @return PqrNotificationService
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2021
     */
    public function getService(): PqrNotificationService
    {
        return new PqrNotificationService($this);
    }

    /**
     * obtiene los datos del funcionario
     *
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function getFkFuncionario(): array
    {
        return [
            'text' => $this->getFuncionario()->getName(),
            'id'   => $this->getFuncionario()->getPK()
        ];
    }
}
