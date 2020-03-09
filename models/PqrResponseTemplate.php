<?php

namespace Saia\Pqr\Models;

use Saia\core\model\Model;

class PqrResponseTemplate extends Model
{

    public function __construct($id = null)
    {
        parent::__construct($id);
    }

    protected function defineAttributes(): void
    {
        $this->dbAttributes = (object) [
            'safe' => [
                'name',
                'content'
            ],
            'primary' => 'id',
            'table' => 'pqr_response_templates'
        ];
    }
}
