<?php

namespace Saia\Pqr\models;

use Saia\core\model\Model;
use Saia\models\documento\Documento;
use Saia\Pqr\formatos\pqr\FtPqr;

class PqrBackup extends Model
{
    use TModel;

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
                ],
                'FtPqr' => [
                    'model' => FtPqr::class,
                    'attribute' => 'idft_pqr',
                    'primary' => 'fk_pqr',
                    'relation' => self::BELONGS_TO_ONE
                ]
            ]
        ];
    }

    /**
     * Obtiene los valores de data decodificado
     *
     * @return object
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function getData(): object
    {
        return json_decode($this->data);
    }
}
