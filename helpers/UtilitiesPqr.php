<?php

namespace Saia\Pqr\Helpers;

use Saia\models\formatos\Formato;
use Saia\models\vistas\VfuncionarioDc;

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
            crear_destino($dest);
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

    public static function notifyAdministrator(string $message): void
    {
        //TODO: ENVIAR CORREO AL ADMINISTRADOR CON EL ERROR
        self::saveLog($message);
    }

    public static function saveLog(string $message)
    {
        global $rootPath;

        $path = $rootPath . 'app/modules/back_pqr/logs/';
        crear_destino($path);

        $data = [
            'date' => date('Y-m-d H:i:s'),
            'message' => $message
        ];
        file_put_contents($path . 'log.txt', json_encode($data) . "\n", FILE_APPEND);
    }
}
