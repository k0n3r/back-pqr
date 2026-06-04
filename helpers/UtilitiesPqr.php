<?php

namespace App\Bundles\pqr\helpers;

use App\Bundles\pqr\formatos\pqr\FtPqr;
use App\Bundles\pqr\formatos\pqr_respuesta\FtPqrRespuesta;
use Saia\core\model\ModelFormat;
use Saia\models\documento\Documento;
use Saia\models\formatos\Formato;
use Saia\models\tarea\TareaEstado;

class UtilitiesPqr
{
    private static ?Formato $FormatoPqr = null;
    private static ?Formato $FormatoPqrRespuesta = null;

    private static function getFormatPqr(): Formato
    {
        if (!static::$FormatoPqr) {
            static::$FormatoPqr = Formato::findByAttributes([
                'nombre' => 'pqr',
            ]);
        }

        return static::$FormatoPqr;
    }

    private static function getFormatPqrRespuesta(): Formato
    {
        if (!static::$FormatoPqrRespuesta) {
            static::$FormatoPqrRespuesta = Formato::findByAttributes([
                'nombre' => 'pqr_respuesta',
            ]);
        }

        return static::$FormatoPqrRespuesta;
    }

    /**
     * Obtiene la instancia de la FtPqr o clase que la extienda
     *
     * @param int $documentId
     *
     * @return FtPqr
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2021-10-05
     */
    public static function getInstanceForDocumentId(int $documentId): ModelFormat
    {
        $Documento = new Documento($documentId);

        return $Documento->getFt();
    }

    /**
     * Obtiene la instancia de la FtPqr o clase que la extienda
     *
     * @param int $idft
     *
     * @return FtPqr
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2021-10-05
     */
    public static function getInstanceForFtId(int $idft): FtPqr
    {
        $className = self::getFormatPqr()->getFtClass();

        return new $className($idft);
    }

    /**
     * Obtiene la instancia de la FtPqrRespuesta o clase que la extienda
     *
     * @param int $idft
     *
     * @return FtPqrRespuesta
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2021-10-05
     */
    public static function getInstanceForFtIdPqrRespuesta(int $idft): FtPqrRespuesta
    {
        $className = self::getFormatPqrRespuesta()->getFtClass();

        return new $className($idft);
    }

    /**
     * Obtiene la cantidad de tareas y cantidad de tareas finalizadas
     * del documento
     *
     * @param Documento $Documento
     *
     * @return int[]
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public static function getFinishTotalTask(Documento $Documento): array
    {
        $finish = $cancel = $total = 0;

        if ($Tareas = $Documento->getService()->getTasks()) {
            $total = count($Tareas);

            foreach ($Tareas as $Tarea) {
                $TareaService = $Tarea->getService();
                switch ($TareaService->getState()->valor) {
                    case TareaEstado::REALIZADA:
                        $finish++;
                        break;

                    case TareaEstado::CANCELADA:
                        $cancel++;
                        break;
                }
            }
        }

        return [
            'finish' => $finish,
            'cancel' => $cancel,
            'total'  => $total,
        ];
    }

    /**
     * Retorna la imagen del QR
     *
     * @param FtPqr $FtPqr
     *
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public static function showQr(FtPqr $FtPqr): string
    {
        return '<img src="/'.$FtPqr->getDocument()->getQR().'" width="80px" height="80px" />';
    }
}
