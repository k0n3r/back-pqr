<?php

namespace App\Bundles\pqr\Services\generadoresWs;

use App\Bundles\pqr\Services\controllers\WebserviceCalificacion;
use App\services\generadoresWs\GenerateWsFt;
use Saia\controllers\generator\webservice\IWsHtml;

class GenerateWsPqrCalificacion extends GenerateWsFt
{

    protected function getGenerateSearch(): bool
    {
        return false;
    }

    protected function getIWsHtml(): IWsHtml
    {
        return new WebserviceCalificacion($this->Formato);
    }
}