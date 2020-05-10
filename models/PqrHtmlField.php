<?php

namespace Saia\Pqr\models;

use Saia\core\model\Model;

class PqrHtmlField extends Model
{

    public function __construct($id = null)
    {
        parent::__construct($id);
    }

    protected function defineAttributes(): void
    {
        $this->dbAttributes = (object) [
            'safe' => [
                'label',
                'type',
                'type_saia',
                'active'
            ],
            'primary' => 'id',
            'table' => 'pqr_html_fields'
        ];
    }
}
