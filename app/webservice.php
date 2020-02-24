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
use Saia\Pqr\Models\PqrForm;
use Saia\core\DatabaseConnection;
use Saia\models\vistas\VfuncionarioDc;
use Saia\controllers\GuardarFtController;
use Saia\controllers\UtilitiesController;
use Saia\Pqr\Helpers\UtilitiesPqr;

$Response = (object) [
    'message' => '',
    'success' => 0,
];

try {
    $Connection = DatabaseConnection::beginTransaction();

    //SessionController::refresh(new Funcionario(Funcionario::RADICADOR_WEB));

    $formatId = $_REQUEST['formatId'];
    $PqrForm = PqrForm::getPqrFormActive();

    if ($PqrForm->fk_formato != $formatId || !$formatId) {
        throw new Exception("Error Processing Request", 1);
    }

    $request = UtilitiesController::cleanForm($_REQUEST);

    $iddependenciaCargo = VfuncionarioDc::getFirstUserRole(Funcionario::RADICADOR_WEB);
    if (!$iddependenciaCargo) {
        UtilitiesPqr::notifyAdministrator(
            "El funcionario con login 'radicador_web' NO tiene roles activos"
        );
        throw new Exception("Error Processing Request", 1);
    }

    $newData = array_merge($request, [
        'dependencia' => $iddependenciaCargo,
        'tipo_radicado' => $PqrForm->Contador->nombre
    ]);

    $GuardarFtController = new GuardarFtController($formatId, $newData);
    if (!$GuardarFtController->create()) {
        throw new Exception("No fue posible radicar el documento2", 1);
    }
    $Response->success = 1;
    $Connection->commit();
} catch (Throwable $th) {
    $Connection->rollBack();
    $Response->message = $th->getMessage();
}

//SessionController::logoutWebservice();

echo json_encode($Response);
