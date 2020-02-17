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
use Saia\models\Funcionario;
use Saia\core\DatabaseConnection;
use Saia\controllers\SessionController;
use Saia\controllers\GuardarFtController;

$Response = (object) [
    'message' => '',
    'success' => 0,
];

//SessionController::refresh(new Funcionario(Funcionario::RADICADOR_WEB));

if ($idformato = $_REQUEST['formatId']) {
    //throw new Exception("Error Processing Request", 1);
}

try {
    $Connection = DatabaseConnection::beginTransaction();

    // $GuardarFtController = new GuardarFtController($idformato);
    // $GuardarFtController->create($_REQUEST);

    $Response->success = 1;

    $Connection->commit();
} catch (Throwable $th) {
    $Connection->rollBack();
    $Response->message = $th->getMessage();
}

SessionController::logoutWebservice();

echo json_encode($Response);
