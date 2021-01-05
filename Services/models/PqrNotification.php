<?php

namespace App\Bundles\pqr\Services\models;

use Saia\core\model\Model;
use Saia\models\Funcionario;

class PqrNotification extends Model
{
    use TModels;

    protected function defineAttributes(): void
    {
        $this->dbAttributes = (object) [
            'safe' => [
                'fk_funcionario',
                'fk_pqr_form',
                'email',
                'notify'
            ],
            'primary' => 'id',
            'table' => 'pqr_notifications',
            'relations' => [
                'Funcionario' => [
                    'model' => Funcionario::class,
                    'attribute' => 'idfuncionario',
                    'primary' => 'fk_funcionario',
                    'relation' => self::BELONGS_TO_ONE
                ]
            ]
        ];
    }

    /**
     * obtiene los datos del funcionario
     *
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function getFkFuncionario()
    {
        return [
            'text' => $this->Funcionario->getName(),
            'id' => $this->Funcionario->getPK()
        ];
    }
}
