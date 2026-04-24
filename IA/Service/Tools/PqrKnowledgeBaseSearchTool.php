<?php

declare(strict_types=1);

namespace App\Bundles\pqr\IA\Service\Tools;

use App\Bundles\ia\Services\Tools\KnowledgeBaseSearchTool;
use App\Bundles\pqr\IA\Service\PqrIaGuard;
use Symfony\AI\Agent\Toolbox\Attribute\AsTool;

/**
 * Wrapper de {@see KnowledgeBaseSearchTool} pre-configurado para el módulo PQR.
 *
 * Pre-inyecta automáticamente el processId del proceso PQR en cada búsqueda,
 * de modo que el agente ia_pqr nunca filtra por proceso incorrecto y no
 * necesita conocer el ID interno del proceso.
 *
 * Parámetros eliminados respecto al tool genérico:
 *  - processId → siempre el proceso PQR (transparente para el LLM)
 *
 * Requiere que PQR esté configurado como proceso IA en ia_process.
 * Si no lo está, retorna un mensaje de error en lugar de buscar sin filtro.
 */
#[AsTool(
    name: KnowledgeBaseSearchTool::TOOL_NAME,
    description: <<<'DESC'
        Busca información sobre PQRs indexada en la base de conocimiento mediante búsqueda semántica.
        
        FILTROS disponibles:
        - documentId       → PQR específica y sus anexos directos.
        - parentDocumentId → documentos hijos directos (ej. respuestas inmediatas a una PQR).
        - rootDocumentId   → árbol completo: PQR raíz + respuestas + calificaciones. Usar para historial completo del caso.
        - radicated        → número de radicado completo (formato: YYYYMMDD-NNN-X, ej: "20260328-23-I").
        - consecutive      → número de consecutivo (entero, ej: 23). Usar cuando el usuario diga "PQR número 23" o "consecutivo 23".
        - attachmentLabel  → nombre parcial o completo de un anexo (ej: "contrato.pdf"). Combinar con documentId o rootDocumentId para acotar.
        - userId           → idfuncionario del creador. SOLO incluir cuando el sistema indique explícitamente acceso restringido (fullAccess=false).
        DESC,
)]
readonly class PqrKnowledgeBaseSearchTool
{
    public function __construct(
        private KnowledgeBaseSearchTool $inner,
        private PqrIaGuard $guard,
    ) {
    }

    /**
     * @param string $query Consulta en lenguaje natural
     * @param int|null $documentId PQR específica y sus anexos
     * @param int|null $parentDocumentId Hijos directos de un documento
     * @param int|null $rootDocumentId Árbol completo del caso
     * @param string|null $radicated Número de radicado
     * @param int|null $consecutive Número de consecutivo
     * @param string|null $attachmentLabel Nombre de anexo
     * @param int|null $userId idfuncionario del creador — SOLO cuando el sistema indique acceso restringido (fullAccess=false)
     */
    public function __invoke(
        string $query,
        ?int $documentId = null,
        ?int $parentDocumentId = null,
        ?int $rootDocumentId = null,
        ?string $radicated = null,
        ?int $consecutive = null,
        ?string $attachmentLabel = null,
        ?int $userId = null,
    ): string {
        $processId = $this->guard->getPqrProcessId();
        if ($processId === null) {
            return 'El módulo PQR no está configurado como proceso de IA.';
        }

        return ($this->inner)(
            query: $query,
            processId: $processId,
            documentId: $documentId,
            parentDocumentId: $parentDocumentId,
            rootDocumentId: $rootDocumentId,
            radicated: $radicated,
            consecutive: $consecutive,
            attachmentLabel: $attachmentLabel,
            userId: $userId,
        );
    }
}
