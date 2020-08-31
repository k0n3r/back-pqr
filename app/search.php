<?php

use Doctrine\DBAL\Types\Types;
use Saia\core\DatabaseConnection;
use Saia\controllers\DateController;
use Saia\models\documento\Documento;
use Saia\controllers\SessionController;
use Saia\controllers\functions\RequestProcessor;
use Saia\Pqr\formatos\pqr\FtPqr;

$max_salida = 10;
$rootPath = $ruta = '';

while ($max_salida > 0) {
    if (is_file($ruta . 'index.php')) {
        $rootPath = $ruta;
        break;
    }

    $ruta .= '../';
    $max_salida--;
}

include_once $rootPath . 'app/vendor/autoload.php';

$Response = (object) [
    'data' => [],
    'message' => '',
    'success' => 0,
];

try {
    $Connection = DatabaseConnection::getDefaultConnection();
    $Connection->beginTransaction();

    SessionController::goUp($_REQUEST['token'], $_REQUEST['key']);

    $newData = RequestProcessor::cleanForm($_REQUEST);

    if (empty($newData['numero'])) {
        throw new Exception("Se debe indicar el numero de radicado", 200);
    }

    $Qb = $Connection->createQueryBuilder()
        ->select('ft.*')
        ->from('ft_pqr', 'ft')
        ->join('ft', 'documento', 'd', 'ft.documento_iddocumento=d.iddocumento')
        ->where('d.estado<>:estado')
        ->setParameter(':estado', Documento::ELIMINADO, Types::STRING)
        ->andWhere('d.numero = :numero')
        ->setParameter(':numero', $newData['numero'], Types::INTEGER);

    $records = FtPqr::findByQueryBuilder($Qb);

    $data = [];
    if ($records) {
        foreach ($records as $Ftpqr) {
            $data[] = [
                'fecha' => DateController::convertDate($Ftpqr->Documento->fecha),
                'descripcion' => array_filter(explode("<br>", $FtPqr->Documento->getDescription())),
                'url' => $Ftpqr->getUrlQR()
            ];
        }
    }

    $Response->data = $data;
    $Response->success = 1;
    $Connection->commit();
} catch (Throwable $th) {
    $Connection->rollBack();
    $Response->message = $th->getMessage();
}

echo json_encode($Response);
