<?php

/**
 * Configuración condicional de servicios IA para el módulo PQR.
 *
 * Solo se registra cuando el módulo IA está instalado.
 * Si el módulo IA no existe, este archivo retorna sin registrar nada
 * y el módulo PQR funciona normalmente sin las funcionalidades IA.
 */

use App\Bundles\ia\Controller\AbstractModuleChatController;
use App\Bundles\pqr\IA\Service\Tools\PqrStatsTool;
use App\Bundles\pqr\IA\Service\Tools\PqrTool;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    if (!class_exists(AbstractModuleChatController::class)) {
        return;
    }

    $services = $container->services()
        ->defaults()
        ->autowire()
        ->autoconfigure();

    $services->load('App\\Bundles\\pqr\\IA\\', '../../IA/');

    // Registra PqrTool en el agente IA (equivalente a ia.yaml)
    $container->extension('ai', [
        'agent' => [
            'ia' => [
                'tools' => [
                    ['service' => PqrTool::class],
                    ['service' => PqrStatsTool::class],
                ],
            ],
        ],
    ]);
};
