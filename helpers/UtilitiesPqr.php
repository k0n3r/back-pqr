<?php

namespace App\Bundles\pqr\helpers;

use Saia\models\Configuracion;
use App\Bundles\pqr\formatos\pqr\FtPqr;
use Saia\models\tarea\TareaEstado;
use Saia\models\documento\Documento;
use Saia\controllers\SendMailController;
use Saia\controllers\TemporalController;
use App\Bundles\pqr\Services\controllers\QRDocumentoPqrController;

class UtilitiesPqr
{

    /**
     * Copia un archivo o directorio
     *
     * @param string $source Carpeta/archivo origen
     * @param string $dest Carpeta/archivo destino
     * @return boolean
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public static function copyToDir(string $source, string $dest): bool
    {
        if (is_link($source)) {
            return symlink(readlink($source), $dest);
        }

        if (is_file($source)) {
            return copy($source, $dest);
        }

        if (!is_dir($dest)) {
            TemporalController::createFolder($dest);
        }

        $dir = dir($source);
        while (false !== $entry = $dir->read()) {
            if ($entry == '.' || $entry == '..') {
                continue;
            }

            self::copyToDir("$source/$entry", "$dest/$entry");
        }

        $dir->close();

        return true;
    }

    public static function notifyAdministrator(string $message, ?array $log = null): void
    {
        if ($log) {
            self::saveLog($log);
        }

        $SendMailController = new SendMailController(
            "Notificación del sistema",
            $message
        );

        if ($Configuracion = Configuracion::findByAttributes([
            'nombre' => 'correo_administrador'
        ])) {
            if (filter_var($Configuracion->valor, FILTER_VALIDATE_EMAIL)) {
                $SendMailController->setDestinations(
                    SendMailController::DESTINATION_TYPE_EMAIL,
                    [$Configuracion->valor]
                );
            }
        }

        $SendMailController->setHiddenCopyDestinations(
            SendMailController::DESTINATION_TYPE_EMAIL,
            ["andres.agudelo@cerok.com"]
        );

        $SendMailController->send();
    }

    public static function saveLog(array $log): void
    {

        $path = ROOT_PATH . 'src/Bundles/pqr/logs/';
        TemporalController::createFolder($path);

        $data = [
            'date' => date('Y-m-d H:i:s'),
            'log' => $log
        ];
        file_put_contents($path . 'log.txt', json_encode($data) . "\n", FILE_APPEND);
    }

    /**
     * Obtiene la cantidad de tareas y cantidad de tareas finalizadas 
     * del documento
     *
     * @param Documento $Documento
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public static function getFinishTotalTask(Documento $Documento): array
    {
        $finish = $total = 0;

        if ($Tareas = $Documento->getService()->getTasks()) {
            $total = count($Tareas);

            foreach ($Tareas as $Tarea) {
                $TareaService = $Tarea->getService();
                if (
                    $TareaService->getState()->valor == TareaEstado::REALIZADA ||
                    $TareaService->getState()->valor == TareaEstado::CANCELADA
                ) {
                    $finish = $finish + 1;
                }
            }
        }

        return [
            'finish' => $finish,
            'total' => $total
        ];
    }

    /**
     * Valida si un email es valido
     *
     * @param string $email
     * @return boolean
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public static function isEmailValid(string $email): bool
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        return true;
    }

    /**
     * Retorna la imagen del QR
     *
     * @param FtPqr $FtPqr
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public static function showQr(FtPqr $FtPqr): string
    {
        $QRDocumentoPqrController = new QRDocumentoPqrController($FtPqr);
        return $QRDocumentoPqrController->getImageQR();
    }
}
