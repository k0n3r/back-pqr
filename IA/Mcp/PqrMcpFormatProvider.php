<?php

declare(strict_types=1);

namespace App\Bundles\pqr\IA\Mcp;

use App\Bundles\ia\Mcp\McpFormatProviderInterface;
use App\Bundles\pqr\Service\PqrFormProvider;

/**
 * Registra el formato PQR en el servidor MCP.
 *
 * El idformato se obtiene dinámicamente desde PqrFormProvider (tabla pqr_form),
 * igual que el resto del módulo PQR, evitando hardcodear un ID que
 * puede variar entre instancias.
 */
class PqrMcpFormatProvider implements McpFormatProviderInterface
{
    public function __construct(
        private readonly PqrFormProvider $pqrFormProvider,
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
}
