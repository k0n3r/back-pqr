<?php

namespace App\Bundles\pqr\Services\models;

use Saia\core\model\Model;
use Saia\models\documento\Documento;
use App\Bundles\pqr\formatos\pqr\FtPqr;

class PqrBackup extends Model
{
    use TModels;

    protected function defineAttributes(): void
    {
        $this->dbAttributes = (object) [
            'safe' => [
                'fk_documento',
                'fk_pqr',
                'data_json'
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
    public function getDataJson(): object
    {
        return json_decode($this->data_json);
    }
}
