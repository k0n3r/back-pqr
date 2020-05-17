<?php

namespace Saia\Pqr\models;

use Saia\core\model\Model;

class PqrHtmlField extends Model
{
    const TYPE_DEPENDENCIA = 'dependencia';
    const TYPE_LOCALIDAD = 'localidad';

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
                'active'
            ],
            'primary' => 'id',
            'table' => 'pqr_html_fields'
        ];
    }
}
