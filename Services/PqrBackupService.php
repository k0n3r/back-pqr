<?php

namespace App\Bundles\pqr\Services;

use App\Bundles\pqr\Services\models\PqrBackup;
use App\services\models\ModelService;

class PqrBackupService extends ModelService
{
    /**
     * @inheritDoc
     */
    public function getModel(): PqrBackup
    {
        return $this->Model;
    }
}
