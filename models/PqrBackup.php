<?php

namespace Saia\Pqr\models;

use Saia\core\model\Model;
use Saia\models\documento\Documento;

class PqrBackup extends Model
{

    public function __construct($id = null)
    {
        parent::__construct($id);
    }

    protected function defineAttributes(): void
    {
        $this->dbAttributes = (object) [
            'safe' => [
                'fk_documento',
                'fk_pqr',
                'data'
            ],
            'primary' => 'id',
            'table' => 'pqr_backups',
            'relations' => [
                'Documento' => [
                    'model' => Documento::class,
                    'attribute' => 'iddocumento',
                    'primary' => 'fk_documento',
                    'relation' => self::BELONGS_TO_ONE
                ]
            ]
        ];
    }
}
