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

    $className = "Saia\\Pqr\\Controllers\\$class";

    $Controller = new $className($newData);
    $Response = $Controller->$method();

    $Response->notifications = NotifierController::prepare();
} catch (Throwable $th) {
    $Response->message = $th->getMessage();
}

echo json_encode($Response);
