<?php

namespace App\Bundles\pqr\IA\Service;

use App\Bundles\ia\Services\FormatoDocumentProcessorInterface;
use Saia\controllers\SaveDocument;
use Saia\models\formatos\Formato;
use Saia\models\vistas\VfuncionarioDc;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;

readonly class PqrDocumentProcessor implements FormatoDocumentProcessorInterface
{
    public function __construct(
        private Security $security,
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
    public function process(Formato $formato, array $payload): JsonResponse
    {
        $VfuncionarioDc = VfuncionarioDc::getActiveRoles($this->security->getUser()->getId())[0];
        $saveDocument = new SaveDocument($formato, $VfuncionarioDc);
        $saveDocument->createOrUpdateDocument(array_merge($payload, [
            'webservice'  => 1,
            'dependencia' => $VfuncionarioDc->iddependencia,
        ]));

        $document = $saveDocument->getDocument();
        $radicado = $document->getService()->getFilingReferenceNumber();
        $result = [
            'data'    => [
                'documentId'  => $document->getPK(),
                'radicado'    => $radicado,
                'consecutivo' => $document->numero,
            ],
            'message' => 'Se ha generado la PQR con radicado '.$radicado,
        ];

        return new JsonResponse($result);
    }
}
