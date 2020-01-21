<?php

namespace Saia\Pqr\Models;

use Saia\core\model\Model;
use Saia\models\Contador;
use Saia\models\formatos\Formato;
use Saia\Pqr\Models\PqrFormField;

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
                'fk_contador',
                'label',
                'active'
            ],
            'primary' => 'id',
            'table' => 'pqr_forms',
            'relations' => [
                'Formato' => [
                    'model' => Formato::class,
                    'attribute' => 'idformato',
                    'primary' => 'fk_formato',
                    'relation' => self::BELONGS_TO_ONE
                ],
                'Contador' => [
                    'model' => Contador::class,
                    'attribute' => 'idcontador',
                    'primary' => 'fk_contador',
                    'relation' => self::BELONGS_TO_ONE
                ],
                'PqrFormFields' => [
                    'model' => PqrFormField::class,
                    'attribute' => 'fk_pqr_form',
                    'primary' => 'id',
                    'relation' => self::BELONGS_TO_MANY
                ]
            ]
        ];
    }
}
