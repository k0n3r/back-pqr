<?php

/**
 * Rutas IA del módulo PQR — cargadas solo si el módulo IA está instalado.
 */

use App\Bundles\ia\Controller\AbstractModuleChatController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes): void {
    if (!class_exists(AbstractModuleChatController::class)) {
        return;
    }

    $routes->import('../../IA/Controller/', 'attribute')
        ->prefix('/api/pqr')
        ->namePrefix('pqr_ia_');
};
