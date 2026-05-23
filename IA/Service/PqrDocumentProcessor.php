<?php

namespace App\Bundles\pqr\IA\Service;

use App\Bundles\ia\Services\DocumentCreatedDto;
use App\Bundles\ia\Services\FormatoDocumentProcessorInterface;
use App\Service\PathResolver;
use Saia\controllers\anexos\FileJson;
use Saia\controllers\SaveDocument;
use Saia\models\formatos\Formato;
use Saia\models\vistas\VfuncionarioDc;
use Symfony\Bundle\SecurityBundle\Security;

readonly class PqrDocumentProcessor implements FormatoDocumentProcessorInterface
{
    public function __construct(
        private Security $security,
        private PathResolver $pathResolver,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getFormatName(): string
    {
        return PqrIaGuard::FORMAT_NAME;
    }

    /**
     * @inheritDoc
     */
    public static function getPriority(): int
    {
        return 0;
    }

    /**
     * @inheritDoc
     */
    public function process(Formato $formato, array $payload): DocumentCreatedDto
    {
        $VfuncionarioDc = VfuncionarioDc::getActiveRoles($this->security->getUser()->getId())[0];
        $saveDocument   = new SaveDocument($formato, $VfuncionarioDc);
        $saveDocument->createOrUpdateDocument(array_merge($payload, [
            'webservice'  => 1,
            'dependencia' => $VfuncionarioDc->iddependencia,
        ]));

        $document     = $saveDocument->getDocument();
        $fileTemporal = (new FileJson($document->getPdfJson()))->getTemporalPath();
        $pdfUrl       = $this->pathResolver->generateURL($fileTemporal);
        $radicado     = $document->getService()->getFilingReferenceNumber();

        return new DocumentCreatedDto(
            documentId : $document->getPK(),
            radicado   : $radicado,
            consecutivo: $document->numero,
            pdfUrl     : $pdfUrl,
            message    : 'Se ha generado la PQR con radicado '.$radicado,
        );
    }
}
