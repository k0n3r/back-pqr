<?php

declare(strict_types=1);

namespace App\Bundles\pqr\IA\Dto;

use App\Bundles\ia\Dto\askChatForUser;
use App\Bundles\ia\Dto\ModuleFormatAwareChat;

/**
 * DTO para el chat IA sobre una PQR.
 *
 * Extiende el núcleo agregando:
 * - La herramienta `create_response_pqr` con su flujo de confirmación obligatorio.
 *
 * El comportamiento varía según si el documento está indexado en la KB:
 * - Con KB:    extraToolsSections()           → incluye referencia a `knowledge_base_search` en el flujo.
 * - Sin KB:    extraToolsSectionsNotIndexed() → el flujo trabaja solo con <informacion>.
 */
readonly class askChatForPqr extends askChatForUser implements ModuleFormatAwareChat
{
    public function getModuleFormatName(): string
    {
        return 'ft_pqr';
    }

    /**
     * Herramientas disponibles cuando el documento SÍ está indexado en la KB.
     *
     * @return string[]
     */
    protected function extraToolsSections(): array
    {
        return [
            $this->buildToolCreateResponsePqr(withKbSearch: true),
        ];
    }

    /**
     * Herramientas disponibles cuando el documento NO está indexado en la KB.
     *
     * @return string[]
     */
    protected function extraToolsSectionsNotIndexed(): array
    {
        return [
            $this->buildToolCreateResponsePqr(withKbSearch: false),
        ];
    }

    /**
     * Instrucciones para el uso de la herramienta `create_response_pqr`.
     *
     * El flujo de confirmación está aquí (en el system prompt) y NO en el #[AsTool] description,
     * que debe mantenerse como una descripción funcional breve.
     *
     * @param bool $withKbSearch Indica si `knowledge_base_search` está disponible en este contexto.
     */
    private function buildToolCreateResponsePqr(bool $withKbSearch): string
    {
        $documentId = $this->getDocumentId();

        $consultaContenido = $withKbSearch
            ? '1. Si no conoces el contenido completo de la PQR, revisa <informacion>. Si no es suficiente, usa `knowledge_base_search` UNA sola vez con `rootDocumentId: '.$documentId.'`'
            : 'Si no conoces el contenido completo de la PQR, revisa exclusivamente <informacion>. No uses herramientas de búsqueda externas.';

        return <<<TEXT
            ## HERRAMIENTA: `create_response_pqr`
            Registra una respuesta oficial a la PQR en el sistema.
            
            ### Cuándo usarla
            Cuando el funcionario solicite redactar o ayudar con una respuesta. No la invoques de forma automática ni sin solicitud previa.

            ### Flujo obligatorio antes de invocarla
            1. $consultaContenido
            2. Redacta un borrador y muéstralo al funcionario:
               - Sin saludo inicial.
               - Sin despedida.
               - Solo el contenido sustantivo de la respuesta.
            3. Pregunta de forma explícita:
               "¿Confirmas que deseas registrar esta respuesta en el sistema?"
            4. Si el funcionario solicita cambios:
               - Ajusta el borrador.
               - Vuelve a mostrar la versión actualizada.
               - Solicita confirmación nuevamente.
            5. Solo tras confirmación clara y explícita del funcionario:
               - No vuelvas a mostrar el texto del borrador.
               - Invoca directamente `create_response_pqr`.
            
            ### Parámetros obligatorios
            | Parámetro | Valor |
            |-----------|-------|
            | `documentId` | $documentId (fijo, no modificable) |
            | `contentAnswers` | Texto completo aprobado por el funcionario |
            | `subject` | Asunto de la respuesta |
            
            ### Manejo del resultado
            Cuando la herramienta retorne un texto que contenga `[open_document:N]`:
            - Incluye ese fragmento exactamente como aparece, sin modificaciones.
            - No lo expliques ni lo sustituyas por otras palabras.
            TEXT;
    }
}
