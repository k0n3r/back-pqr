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

use Exception;
use ReflectionClass;
use Saia\models\Funcionario;
use Saia\controllers\SessionController;
use Saia\controllers\functions\RequestProcessor;
use Saia\Pqr\controllers\RequestProcessorController;

$Response = (object) [
    'message' => '',
    'success' => 0,
];

try {
    SessionController::refresh(new Funcionario(Funcionario::RADICADOR_WEB));

    RequestProcessor::removeCredentials();

    $newData = RequestProcessor::cleanForm($_REQUEST);

    if (empty($method = $newData['method'])) {
        throw new Exception("Error Processing Request", 1);
    }
    unset($newData['method']);

    $Reflection = new ReflectionClass(RequestProcessorController::class);
    if ($Reflection->hasMethod($method)) {
        $Instancia = $Reflection->newInstanceArgs($newData);
        $Response = $Instancia->$method();
    } else {
        throw new Exception("Error Processing Request", 1);
    }
} catch (Throwable $th) {
    $Response->message = $th->getMessage();
}

echo json_encode($Response);
