<?php

namespace App\Bundles\pqr\Services\generadoresWs;

use App\Bundles\pqr\Services\controllers\WebservicePqr;
use App\services\generadoresWs\GenerateWsFt;
use Saia\controllers\generator\webservice\WsGenerator;

class GenerateWsPqr extends GenerateWsFt
{
    protected function getGenerateSearch(): bool
    {
        return false;
    }

    public function generateEdit(): bool
    {
        return false;
    }

    public function getIWsHtml(): WebservicePqr
    {
        $WebservicePqr = new WebservicePqr($this->Formato);
        $WebservicePqr->setHtmlTemplate('src/Bundles/pqr/Services/controllers/templates/formPqr.html.php');
        $WebservicePqr->setJsTemplate('src/Bundles/pqr/Services/controllers/templates/formPqr.js.php');

        return $WebservicePqr;
    }

    protected function executeMoreActions(): void
    {
        $WsGenerator = $this->getWsGenerator();
        $folder = 'src/Bundles/pqr/Services/controllers/templates/';

        $page404 = WsGenerator::generateFileForWs('src/legacy/controllers/generator/webservice/templates/404.html');
        $infoQrFile = WsGenerator::generateFileForWs($folder . 'infoQR.html');
        $infoQRJsFile = WsGenerator::generateFileForWs($folder . 'infoQR.js');
        $timelineFile = WsGenerator::generateFileForWs($folder . 'TimeLine.js');

        $WsGenerator->addFiles([$infoQrFile, $infoQRJsFile, $timelineFile, $page404]);
    }
}