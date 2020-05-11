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
use Saia\models\formatos\Formato;
use Saia\controllers\SaveDocument;
use Saia\Pqr\Helpers\UtilitiesPqr;
use Saia\models\vistas\VfuncionarioDc;
use Saia\controllers\functions\RequestProcessor;

$Response = (object) [
    'message' => '',
    'success' => 1,
];

try {
    $Connection = DatabaseConnection::beginTransaction();

    if (!$_REQUEST['anterior']) {
        throw new Exception("Error Processing Request");
    }

    if (!$Formato = Formato::findByAttributes([
        'nombre' => 'pqr_calificacion'
    ])) {
        throw new Exception("Error Processing Request");
    }

    $request = RequestProcessor::cleanForm($_REQUEST);

    $iddependenciaCargo = VfuncionarioDc::getFirstUserRole(Funcionario::RADICADOR_WEB);
    if (!$iddependenciaCargo) {
        UtilitiesPqr::notifyAdministrator(
            "El funcionario con login 'radicador_web' NO tiene roles activos"
        );
        throw new Exception("Error Processing Request");
    }

    $newData = array_merge($request, [
        'dependencia' => $iddependenciaCargo,
        'tipo_radicado' => $Formato->Contador->nombre
    ]);

    $GuardarFtController = new SaveDocument($Formato, $newData);
    if (!$GuardarFtController->create()) {
        throw new Exception("No fue posible calificar el servicio", 200);
    }
    $Response->success = 1;
    $Connection->commit();
} catch (Throwable $th) {
    $Connection->rollBack();
    $Response->message = $th->getMessage();
    $Response->code = $th->getCode();
}

echo json_encode($Response);
