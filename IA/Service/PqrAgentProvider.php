<?php

declare(strict_types=1);

namespace App\Bundles\pqr\IA\Service;

use App\Bundles\ia\Services\ModuleAgentProviderInterface;
use App\Bundles\pqr\IA\Service\Tools\PqrStatsTool;
use App\Bundles\pqr\IA\Service\Tools\PqrTool;
use Symfony\AI\Agent\AgentInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\Target;

/**
 * Proveedor del agente IA especializado en PQR.
 *
 * Registra el agente 'ia_pqr' con las herramientas propias del módulo:
 * - {@see PqrTool}      → crea respuestas oficiales a PQRs
 * - {@see PqrStatsTool} → estadísticas en tiempo real sobre la vista vpqr
 *
 * Se carga condicionalmente desde ia_services.php del bundle PQR,
 * solo cuando el bundle IA está instalado.
 */
readonly class PqrAgentProvider implements ModuleAgentProviderInterface
{
    public function __construct(
        #[Target('ia_pqr')]
        private AgentInterface $pqrAgent,
        private PqrTool $pqrTool,
        private PqrStatsTool $pqrStatsTool,
        #[Autowire('%pqr_ia_model%')]
        private string $modelId,
    ) {
    }

    public function getMainFormatName(): string
    {
        return 'ft_pqr';
    }

    public function getAgent(): AgentInterface
    {
        return $this->pqrAgent;
    }

    public function getModelId(): string
    {
        return $this->modelId;
    }

    /**
     * @return string[]
     */
    public function getAdminToolSections(?int $processId): array
    {
        return [
            $this->pqrTool->getAdminToolSection($processId),
            $this->pqrStatsTool->getAdminToolSection($processId),
        ];
    }

    // ─── Subagente (orquestación "todos los procesos") ──────────────

    public function getSubagentToolName(): string
    {
        return 'pqr_agent';
    }

    public function getSubagentToolDescription(): string
    {
        return <<<'DESC'
            Agente especializado en PQR (Peticiones, Quejas, Reclamos y Sugerencias).
            Usar cuando el usuario necesite:
            - Crear o redactar respuestas oficiales a PQRs
            - Consultar estadísticas de PQR (conteos por estado, dependencia, tipo, etc.)
            - Gestionar el ciclo de vida de PQRs

            NO usar para búsquedas informativas simples sobre una PQR — para eso usar knowledge_base_search directamente.
            DESC;
    }

    public function getSubagentSystemPrompt(): string
    {
        $pqrSection   = $this->pqrTool->getAdminToolSection(null);
        $statsSection = $this->pqrStatsTool->getAdminToolSection(null);

        return <<<TEXT
            Eres un agente especializado en el módulo PQR del sistema SAIA.
            Recibes solicitudes del orquestador con el contexto completo de la conversación.

            ## INSTRUCCIONES DE HERRAMIENTAS

            $pqrSection

            $statsSection

            ## REGLAS
            1. No inventes información. Si no encuentras datos, indícalo.
            2. Para `knowledge_base_search`, no apliques filtros de `processId` ni `userId` por defecto.
            3. Sé conciso y preciso.
            TEXT;
    }
}
