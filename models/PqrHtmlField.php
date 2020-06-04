<?php

namespace Saia\Pqr\models;

use Saia\core\model\Model;

class PqrHtmlField extends Model
{
    use TModel;

    const TYPE_DEPENDENCIA = 'dependencia';
    const TYPE_LOCALIDAD = 'localidad';

    protected function defineAttributes(): void
    {
        $this->dbAttributes = (object) [
            'safe' => [
                'label',
                'type',
                'type_saia',
                'uniq',
                'active'
            ],
            'primary' => 'id',
            'table' => 'pqr_html_fields'
        ];
    }
}
