<?php

namespace App\Bundles\pqr\Services;

use App\Bundles\pqr\Services\models\PqrBalancer;
use App\services\models\ModelService\ModelService;

class PqrBalancerService extends ModelService
{
    /**
     * @inheritDoc
     */
    public function getModel(): PqrBalancer
    {
        return $this->Model;
    }
}