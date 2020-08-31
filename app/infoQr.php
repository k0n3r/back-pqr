<?php

use Saia\controllers\notificaciones\NotifierController;
use Saia\controllers\TemporalController;
use Saia\models\Configuracion;
use Saia\models\documento\Documento;
use Saia\models\formatos\CamposFormato;
use Saia\models\formatos\Formato;
use Saia\models\Funcionario;

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
    'data' => new stdClass(),
    'message' => '',
    'success' => 0,
    'notifications' => ''
];

try {
    if (!$_REQUEST['documentId']) {
        throw new Exception('Documento invalido', 1);
    }

    $Documento = new Documento($_REQUEST['documentId']);
    $idformato = $Documento->formato_idformato;
    $Formato = new Formato($idformato);
    $nombreTabla = $Formato->nombre_tabla;
    $data = [];

    switch ($_REQUEST['tipo']) {

        case 'default':
            $etiquetaFormato = mb_strtoupper($Formato->etiqueta);
            $Configuracion = Configuracion::findByAttributes([
                'nombre' => 'logo'
            ]);
            $image = TemporalController::createTemporalFile($Configuracion->getValue());
            $Funcionario = new Funcionario($Documento->ejecutor);
            $data = [
                "titulo" => $etiquetaFormato,
                "logo" => '<img src="' . ABSOLUTE_SAIA_ROUTE . $image->route . '" width="300px">',
                "numero" => $Documento->numero,
                "fecha" => $Documento->fecha_creacion,
                "creador" => $Funcionario->getName()
            ];
            break;

        case 'campos':
            // Obtiene los campos del formato
            $CamposFormato = CamposFormato::findAllByAttributes(['formato_idformato' => $idformato]);
            $nombreCampo = array();
            $etiquetaCampo = array();

            // Filtra los campos del formato que tienen la opcion descrpcion activada
            foreach ($CamposFormato as $Campo) {
                $acciones = explode(',', $Campo->acciones);
                foreach ($acciones as $accion) {
                    if ($accion == 'p') {
                        $data[] = getFieldValue($Campo, $Documento);
                    }
                }
            }

            $data = json_encode($data);
            break;
    }

    $Response->data = $data;
    $Response->notifications = NotifierController::prepare();
    $Response->success = 1;
} catch (Throwable $th) {
    $Response->message = $th->getMessage();
}

echo json_encode($Response);
