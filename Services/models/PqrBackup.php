<?php

namespace App\Bundles\pqr\Services\models;

use App\Bundles\pqr\Services\PqrBackupService;
use Saia\core\model\Model;

class PqrBackup extends Model
{
    use TModels;

    protected function defineAttributes(): void
    {
        $integer = [
            'fk_documento',
            'fk_pqr',
        ];
        $this->dbAttributes = (object)[
            'safe'    => array_merge($integer,[
                'data_json',
            ]),
            'integer' => $integer,
            'primary' => 'id',
            'table'   => 'pqr_backups',
        ];
    }

    /**
     * @return PqrBackupService
     * @author Andres Agudelo <andres.agudelo@cerok.com> @date 2021-02-23
     */
    public function getService(): PqrBackupService
    {
        return new PqrBackupService($this);
    }

    /**
     * Obtiene los valores de data decodificado
     *
     * @return object
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function getDataJson(): object
    {
        return json_decode($this->data_json);
    }
}
