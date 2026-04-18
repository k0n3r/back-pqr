<?php

declare(strict_types=1);

namespace App\Bundles\pqr\IA\Dto;

use App\Bundles\ia\Dto\askChatForUser;
use App\Bundles\ia\Dto\ModuleFormatAwareChat;
use App\Bundles\pqr\IA\Service\PqrIaGuard;

/**
 * DTO para el chat IA sobre una PQR específica (chat de usuario/funcionario).
 *
 * Inyecta en el system prompt el contexto dinámico de la PQR activa:
 * - El documentId fijo (no puede inferirse del #[AsTool] estático).
 * - Si el documento está o no indexado en la KB (condiciona el uso de knowledge_base_search).
 *
 * El flujo de confirmación y la guía de uso de create_response_pqr están en el
 * atributo #[AsTool] de PqrTool — no se duplican aquí.
 */
readonly class askChatForPqr extends askChatForUser implements ModuleFormatAwareChat
{
    public function getModuleFormatName(): string
    {
        return PqrIaGuard::FORMAT_NAME;
    }

    /**
     * Contexto cuando el documento SÍ está indexado en la KB.
     *
     * @return string[]
     */
    protected function extraToolsSections(): array
    {
        return [$this->buildPqrContext(withKbSearch: true)];
    }

    /**
     * Contexto cuando el documento NO está indexado en la KB.
     *
     * @return string[]
     */
    protected function extraToolsSectionsNotIndexed(): array
    {
        return [$this->buildPqrContext(withKbSearch: false)];
    }

    protected function buildScope(): string
    {
        return <<<TEXT
            ## LÍMITE DE ALCANCE
            Eres un asistente exclusivo para PQR (Peticiones, Quejas y Reclamos).
            Solo puedes responder preguntas relacionadas con esta PQR y su gestión:
            contenido, estado, historial, tiempos de respuesta, respuestas registradas y acciones disponibles.
            Si el usuario solicita chistes, conocimiento general, programación, entretenimiento
            u cualquier tema ajeno a la PQR o al sistema SAIA, responde únicamente con:
            "Solo puedo ayudarte con consultas sobre PQR y su gestión. ¿En qué puedo asistirte?"
            TEXT;
    }

    /**
     * Inyecta el documentId fijo y la disponibilidad de KB en el system prompt.
     *
     * Solo incluye lo que es dinámico por request. La guía de uso de
     * create_response_pqr está en el #[AsTool] description de PqrTool.
     */
    private function buildPqrContext(bool $withKbSearch): string
    {
        $documentId = $this->getDocumentId();

        $kbLine = $withKbSearch
            ? "`knowledge_base_search` disponible — si necesitas más contexto usa `rootDocumentId: $documentId`."
            : "`knowledge_base_search` NO disponible — usa solo <informacion> para conocer el contenido de la PQR.";

        return <<<TEXT
            ## CONTEXTO DE LA PQR ACTIVA
            - `documentId` para `create_response_pqr`: **$documentId** (fijo — no busques otro ID).
            - $kbLine
            TEXT;
    }
}
