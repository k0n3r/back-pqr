<?php

declare(strict_types=1);

namespace App\Bundles\pqr\IA\Service;

use App\Bundles\ia\Services\ModuleAgentProviderInterface;
use Symfony\AI\Agent\AgentInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\Target;

/**
 * Proveedor del agente IA especializado en PQR.
 *
 * Expone el agente 'ia_pqr' (definido en ia.yaml del bundle PQR) al sistema de módulos IA.
 * El agente tiene sus herramientas registradas directamente en YAML; la guía de uso
 * de cada herramienta está en su atributo #[AsTool] description.
 *
 * Se carga condicionalmente desde ia_services.php del bundle PQR,
 * solo cuando el bundle IA está instalado.
 */
readonly class PqrAgentProvider implements ModuleAgentProviderInterface
{
    public function __construct(
        #[Target('ia_pqr')]
        private AgentInterface $pqrAgent,
        #[Autowire('%pqr_ia_model%')]
        private string $modelId,
    ) {}

    public function getMainFormatName(): string
    {
        return PqrIaGuard::FORMAT_NAME;
    }

    public function getAgent(): AgentInterface
    {
        return $this->pqrAgent;
    }

    public function getModelId(): string
    {
        return $this->modelId;
    }

}
