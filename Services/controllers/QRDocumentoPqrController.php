<?php

namespace App\Bundles\pqr\Services\controllers;

use App\Bundles\pqr\formatos\pqr\FtPqr;
use Saia\controllers\QRController;
use Saia\controllers\documento\QRDocumentoController;

class QRDocumentoPqrController extends QRDocumentoController
{
    private FtPqr $FtPqr;

    public function __construct(FtPqr $FtPqr)
    {
        $this->FtPqr = $FtPqr;
        parent::__construct($FtPqr->getDocument());
    }

    protected function configureQR($url = null): void
    {
        $url = $this->FtPqr->getService()->getUrlQR();

        $this->QR = new QRController($url);

        $name = "documento_QR{$this->Documento->getPK()}.png";
        $this->QR->setNameQR($name);
    }

    /**
     * Retorna la imagen del QR
     *
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function getImageQR(): string
    {
        $route = $_SERVER['APP_DOMAIN'] . $this->getRouteQR();
        return '<img src="' . $route . '" width="80px" height="80px" />';
    }
}
