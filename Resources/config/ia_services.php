<?php

/**
 * Configuración condicional de servicios IA para el módulo PQR.
 *
 * Solo se registra cuando el módulo IA está instalado.
 * Si el módulo IA no existe, este archivo retorna sin registrar nada
 * y el módulo PQR funciona normalmente sin las funcionalidades IA.
 *
 * ## Qué hace este archivo
 * 1. Carga todos los servicios del directorio IA/ del módulo PQR.
 * 2. Importa ia.yaml que define el agente 'ia_pqr' con las herramientas PQR.
 *
 * Las herramientas PqrTool y PqrStatsTool NO se añaden al agente genérico 'ia'.
 * Solo están disponibles en el agente 'ia_pqr', que PqrAgentProvider expone
 * al orquestador mediante ModuleAgentProviderInterface.
 *
 * Nota sobre mail-intake (2026-11-13): PQR NO necesita sembrar una fila en
 * IaMailIntakeProcessConfig aquí — un ContainerConfigurator no es el lugar para escribir en BD
 * (solo registra servicios, no ejecuta en tiempo de request), y de todas formas no hace falta:
 * "sin fila para este idformato" ya es el comportamiento actual exacto (sin estrategia de
 * adjuntos, radicationMode=AI_ASSISTED) — ver MailAttachmentResolver::resolveExtractionFor().
 * La fila solo se crea si un administrador decide, desde la pantalla de configuración de
 * procesos del módulo ia, que la PQR de este cliente necesita leer su PDF adjunto (ej.
 * attachmentStrategy='pdf_text_extraction', ya genérica, no hace falta nada nuevo de PQR).
 */

use App\Bundles\ia\Controller\AbstractModuleChatController;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    if (!class_exists(AbstractModuleChatController::class)) {
        return;
    }

    $services = $container
        ->services()
        ->defaults()
        ->autowire()
        ->autoconfigure();

    $services->load('App\\Bundles\\pqr\\IA\\', '../../IA/');

    // Modelo del agente ia_pqr: configurable con IA_PQR_MODEL en .env.local.
    // Si no se define, usa claude-sonnet-5 — mismo modelo migrado el 2026-10-08 para
    // el resto de agentes del bundle ia (más barato y mejor en benchmarks que Haiku
    // 4.5, y aquí además recomendado para redacción de respuestas oficiales).
    $container->parameters()->set('pqr_ia_df_model', 'claude-sonnet-5');
    $container->parameters()->set('pqr_ia_model', '%env(default:pqr_ia_df_model:IA_PQR_MODEL)%');

    // Define el agente 'ia_pqr' con las herramientas exclusivas del módulo PQR.
    $container->import('ia.yaml');

    // Registra ia_pqr como subagente del orquestador.
    // El LLM del orquestador decide cuándo delegar según la descripción.
    $container->extension('ai', [
        'agent' => [
            'ia_orchestrator' => [
                'tools' => [[
                    'agent'       => 'ia_pqr',
                    'name'        => 'pqr_agent',
                    'description' => <<<'DESC'
                        Agente especializado en PQR (Peticiones, Quejas, Reclamos y Sugerencias).
                        Invocar cuando el usuario necesite:
                        - Crear o redactar respuestas oficiales a PQRs
                        - Estadísticas de PQR (conteos por estado, dependencia, tipo, etc.)
                        - Acciones sobre el ciclo de vida de una PQR
                        NO invocar para búsquedas informativas simples — usar knowledge_base_search directamente.
                        DESC,
                ]],
            ],
        ],
    ]);
};
