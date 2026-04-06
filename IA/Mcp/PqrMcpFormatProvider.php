<?php

declare(strict_types=1);

namespace App\Bundles\pqr\IA\Mcp;

use App\Bundles\ia\Mcp\McpFormatProviderInterface;
use App\Bundles\pqr\Services\models\PqrForm;

/**
 * Registra el formato PQR en el servidor MCP.
 *
 * El idformato se obtiene dinámicamente desde PqrForm (tabla pqr_form),
 * igual que el resto del módulo PQR, evitando hardcodear un ID que
 * puede variar entre instancias.
 */
class PqrMcpFormatProvider implements McpFormatProviderInterface
{
    public function getFormatId(): int
    {
        return (int)PqrForm::getInstance()->fk_formato;
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
