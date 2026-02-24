<?php

declare(strict_types=1);

namespace App\Bundles\pqr\IA\Dto;

use App\Bundles\ia\Dto\askChatForUser;

/**
 * DTO para el chat IA sobre una PQR.
 *
 * ──────────────────────────────────────────────────────────
 * CÓMO AGREGAR UN NUEVO TOOL:
 *   1. Crear la clase Tool en src/Bundles/pqr/IA/Service/Tools/
 *   2. Añadirla en ia_services.php (extensión 'ai')
 *   3. Crear un método privado: toolNombreDelTool(): string
 *   4. Añadir $this->toolNombreDelTool() al array en extraToolsSections()
 * ──────────────────────────────────────────────────────────
 */
readonly class askChatForPqr extends askChatForUser
{
    public static function fromArray(array $data): self
    {
        return new self(
            message: $data['message'],
            sessionId: $data['sessionId'] ?? null,
            userId: $data['userId'],
            documentId: $data['documentId'],
            processId: $data['processId'],
            otherParams: $data['otherParams'] ?? [],
        );
    }

    protected function extraToolsSections(): array
    {
        return [
            $this->toolCreateResponsePqr(),
        ];
    }

    private function toolCreateResponsePqr(): string
    {
        $documentId = $this->getDocumentId();

        return <<<TEXT
            ## HERRAMIENTA: `create_response_pqr` — Generar respuesta formal en SAIA
            
            Genera y registra la respuesta oficial a la PQR. Úsala **SOLO** con confirmación
            explícita del funcionario tras revisar el borrador.
            
            **Flujo esperado antes de invocarla:**
            1. Consulta el contenido de la PQR con `knowledge_base_search` si aún no lo conoces.
            2. Redacta el borrador de respuesta y preséntalo al funcionario.
               - NO incluyas saludo inicial (ej. "Cordial saludo", "Estimado", etc.).
               - NO incluyas despedida o fórmula de cierre (ej. "Atentamente", "Cordialmente", etc.).
               - Entrega únicamente el contenido sustantivo de la respuesta.
            3. El funcionario revisa y confirma (o pide ajustes).
            4. Una vez el funcionario confirme el contenido:
               - NO vuelvas a mostrar el texto de la respuesta.
               - NO repitas el contenido previamente aprobado.
               - Procede directamente a invocar la herramienta `create_response_pqr`.
            
            **Parámetros requeridos:**
            - `documentId`: $documentId  ← siempre este valor, no cambia
            - `contentAnswers`: texto completo y aprobado de la respuesta
            - `subject`: asunto de la respuesta

            **Resultado de la herramienta:**
            Cuando la herramienta retorne un texto que contiene `[open_document:N]` (donde N es un número),
            debes incluir ese fragmento **exactamente como aparece**, sin parafrasearlo ni reemplazarlo
            por palabras como "enlace proporcionado" o "documento generado". El sistema lo convierte
            automáticamente en un enlace clicable para el usuario.
            TEXT;
    }
}
