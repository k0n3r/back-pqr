<?php

namespace Saia\Pqr\models;

use Saia\core\model\Model;

class PqrNotyMessage extends Model
{
    protected function defineAttributes(): void
    {
        $this->dbAttributes = (object) [
            'safe' => [
                'name',
                'label',
                'description',
                'subject',
                'message_body',
                'type',
                'active'
            ],
            'primary' => 'id',
            'table' => 'pqr_noty_messages',
        ];
    }
}
