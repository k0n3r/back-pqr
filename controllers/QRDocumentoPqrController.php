<?php

namespace Saia\Pqr\controllers;

use Saia\Pqr\formatos\pqr\FtPqr;
use Saia\controllers\QRController;
use Saia\controllers\CryptController;
use Saia\controllers\documento\QRDocumentoController;

class QRDocumentoPqrController extends QRDocumentoController
{
    private FtPqr $FtPqr;

    public function __construct(FtPqr $FtPqr)
    {
        $this->FtPqr = $FtPqr;
        $this->Documento = $FtPqr->Documento;

        $this->configureQR();
    }

    /**
     * @inheritDoc
     */
    protected function configureQR(): void
    {
        $url = $this->FtPqr->getUrlQR();

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
        $route = ABSOLUTE_SAIA_ROUTE . $this->getRouteQR();
        return '<img src="' . $route . '" width="80px" height="80px" />';
    }
}
