<?php

declare(strict_types=1);

namespace App\Bundles\pqr\IA\Mcp;

use App\Bundles\ia\Mcp\McpFormatProviderInterface;
use App\Bundles\ia\Services\Filing\FilingStatusDto;
use App\Bundles\pqr\Repository\PqrLookupRepository;
use App\Bundles\pqr\Service\PqrFormProvider;
use Saia\controllers\CryptController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Registra el formato PQR en el servidor MCP y gestiona la consulta de radicados.
 *
 * Valida que el email aportado por el usuario aparezca en la descripción
 * del documento antes de retornar el resultado.
 *
 * Incluye la URL pública de detalle (infoQR) para que el orquestador
 * la muestre como botón "Ver más información" en WhatsApp.
 */
class PqrMcpFormatProvider implements McpFormatProviderInterface
{
    public function __construct(
        private readonly PqrFormProvider $pqrFormProvider,
        #[Autowire('%env(APP_DOMAIN)%')]
        private readonly string $domain,
        private readonly PqrLookupRepository $pqrLookupRepository,
    ) {
    }

    public function getFormatId(): int
    {
        return $this->pqrFormProvider->get()->getFkFormato();
    }

    public function getLabel(): string
    {
        return 'PQR';
    }

    public function getDescription(): string
    {
        return 'Petición, Queja o Reclamo. Usar para radicar solicitudes, quejas, reclamos y peticiones de ciudadanos o usuarios.';
    }

    public function getVisibility(): string
    {
        return 'public';
    }

    public function supportsQuery(): bool
    {
        return true;
    }

    public function getQueryFields(): array
    {
        return [
            [
                'name'     => 'email',
                'label'    => 'Si registró un correo electrónico al radicar, ingréselo aquí. De lo contrario, escriba *omitir*:',
                'type'     => 'email',
                'optional' => true,
            ],
        ];
    }

    public function getResultFields(): array
    {
        return [
            ['key' => 'number',            'label' => 'Número de radicado', 'icon' => '📄', 'format' => null],
            ['key' => 'state',             'label' => 'Estado',             'icon' => '🔖', 'format' => null],
            ['key' => 'filed_at',          'label' => 'Radicado el',        'icon' => '📅', 'format' => 'datetime'],
            ['key' => 'response_deadline', 'label' => 'Plazo de respuesta', 'icon' => '⏰', 'format' => 'date'],
        ];
    }

    public function resolve(
        string $filingNumber,
        array  $extraFields,
    ): ?FilingStatusDto {
        $email = $extraFields['email'] ?? '';

        $row = $this->pqrLookupRepository->findFilingByNumberAndEmail($filingNumber, $email);

        if ($row === null) {
            return null;
        }

        $data = [
            'number'            => $row['number'],
            'state'             => $row['state'],
            'filed_at'          => $row['filed_at'],
            'response_deadline' => $row['response_deadline'],
        ];

        return new FilingStatusDto($data, $this->buildInfoUrl($row['pqr_id'], $row['iddocumento']));
    }

    private function buildInfoUrl(int $pqrId, int $documentId): string
    {
        $token = CryptController::encrypt(json_encode([
            'id'         => $pqrId,
            'documentId' => $documentId,
        ]));

        return sprintf('%sws/pqr/infoQR.html?data=%s', $this->domain, urlencode($token));
    }
}
