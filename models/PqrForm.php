<?php

namespace Saia\Pqr\Models;

use Saia\core\model\Model;

class PqrForm extends Model
{
    public function __construct($id = null)
    {
        parent::__construct($id);
    }

    protected function defineAttributes(): void
    {
        $this->dbAttributes = (object) [
            'safe' => [
                'fk_formato',
                'active'
            ],
            'primary' => 'id',
            'table' => 'pqr_forms'
        ];
    }
}
