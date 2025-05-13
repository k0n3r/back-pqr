<?php

namespace App\Bundles\pqr\Services\models;

use Saia\core\model\Model;
use App\Bundles\pqr\Services\PqrNotyMessageService;

class PqrNotyMessage extends Model
{
    protected function defineAttributes(): void
    {
        $this->dbAttributes = (object)[
            'safe'    => [
                'name',
                'label',
                'description',
                'subject',
                'message_body',
                'type',
                'active',
            ],
            'primary' => 'id',
            'table'   => 'pqr_noty_messages',
        ];
    }

    /**
     * Retorna el servicio
     *
     * @return PqrNotyMessageService
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2021
     */
    public function getService(): PqrNotyMessageService
    {
        return new PqrNotyMessageService($this);
    }
}
