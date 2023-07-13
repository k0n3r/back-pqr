<?php

namespace App\Bundles\pqr\Services\generadoresWs;

use App\Bundles\pqr\Services\models\PqrForm;
use App\services\exception\SaiaException;
use App\services\generadoresWs\GenerateWsFt;
use Saia\controllers\generator\webservice\WsGenerator;

class GenerateWsPqr extends GenerateWsFt
{
    protected function getGenerateSearch(): bool
    {
        return false;
    }

    public function generate(): void
    {
        $PqrForm = PqrForm::getInstance();

        $WsGenerator = new WsGenerator(
            $PqrForm->getWebservicePqr(),
            $this->getNameFormat(),
            $this->getGenerateSearch()
        );

        $this->createFiles($WsGenerator);

        if (!$WsGenerator->create()) {
            throw new SaiaException("No fue posible generar el ws: {$this->Formato->etiqueta}");
        }
    }

    protected function createFiles(WsGenerator $WsGenerator): void
    {
        $folder = 'src/Bundles/pqr/Services/controllers/templates/';
        $page404 = WsGenerator::generateFileForWs('src/legacy/controllers/generator/webservice/templates/404.html');
        $infoQrFile = WsGenerator::generateFileForWs($folder . 'infoQR.html');
        $infoQRJsFile = WsGenerator::generateFileForWs($folder . 'infoQR.js');
        $timelineFile = WsGenerator::generateFileForWs($folder . 'TimeLine.js');

        $WsGenerator->addFiles([$infoQrFile, $infoQRJsFile, $timelineFile, $page404]);
    }
}