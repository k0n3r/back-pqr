<?php
$max_salida = 10;
$rootPath = $ruta = '';

while ($max_salida > 0) {
    if (is_file($ruta . 'sw.js')) {
        $rootPath = $ruta;
        break;
    }

    $ruta .= '../';
    $max_salida--;
}

include_once $rootPath . 'app/vendor/autoload.php';

use Saia\controllers\JwtController;
use Saia\controllers\UtilitiesController;
use Saia\controllers\notificaciones\NotifierController;

$Response = (object) [
    'message' => '',
    'success' => 0,
    'notifications' => ''
];

try {
    JwtController::check($_REQUEST['token'], $_REQUEST['key']);
    $newData = UtilitiesController::cleanForm($_REQUEST);

    if (empty($method = $newData['method']) || empty($class = $newData['class'])) {
        throw new Exception("Error Processing Request", 1);
    }
    unset($newData['class'], $newData['method']);

    $Reflection = new ReflectionClass("Saia\\Pqr\\Controllers\\$class");
    if ($Reflection->hasMethod($method)) {

        $Instancia = $Reflection->newInstanceArgs([$newData]);
        $Response = $Instancia->$method();
        $Response->notifications = NotifierController::prepare();
    } else {
        throw new Exception("Error Processing Request", 1);
    }
} catch (Throwable $th) {
    $Response->message = $th->getMessage();
}

echo json_encode($Response);
