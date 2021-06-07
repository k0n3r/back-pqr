<?php


namespace App\Bundles\pqr\Services;


use App\Bundles\pqr\Services\models\PqrResponseTime;
use App\services\models\ModelService\ModelService;

class PqrResponseTimeService extends ModelService
{

    /**
     * @inheritDoc
     */
    public function getModel(): PqrResponseTime
    {
        return $this->Model;
    }
}