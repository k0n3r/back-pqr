<?php

namespace Saia\Pqr\Models;

use Saia\core\model\Model;
use Saia\Pqr\formatos\pqr\FtPqr;
use Saia\models\documento\Documento;

class PqrAnswer extends Model
{

    public function __construct($id = null)
    {
        parent::__construct($id);
    }

    protected function defineAttributes(): void
    {
        $this->dbAttributes = (object) [
            'safe' => [
                'fk_pqr',
                'fk_respuesta',
                'notification',
                'fk_encuesta'
            ],
            'primary' => 'id',
            'table' => 'pqr_answers',
            'relations' => [
                'Pqr' => [
                    'model' => FtPqr::class,
                    'attribute' => 'documento_iddocumento',
                    'primary' => 'fk_pqr',
                    'relation' => self::BELONGS_TO_ONE
                ],
                'DocumentoP' => [
                    'model' => Documento::class,
                    'attribute' => 'iddocumento',
                    'primary' => 'fk_pqr',
                    'relation' => self::BELONGS_TO_ONE
                ],
                'DocumentoR' => [
                    'model' => Documento::class,
                    'attribute' => 'iddocumento',
                    'primary' => 'fk_respuesta',
                    'relation' => self::BELONGS_TO_ONE
                ]
            ]
        ];
    }
}
