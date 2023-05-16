<?php

namespace App\Bundles\pqr\helpers;

use App\services\correo\EmailSaia;
use App\services\correo\SendEmailSaia;
use App\services\GlobalContainer;
use Saia\controllers\anexos\FileJson;
use App\Bundles\pqr\formatos\pqr\FtPqr;
use Saia\controllers\generator\webservice\IWsHtml;
use Saia\core\model\ModelFormat;
use Saia\models\formatos\Formato;
use Saia\models\tarea\TareaEstado;
use Saia\models\documento\Documento;

use Throwable;

class UtilitiesPqr
{
    private static ?Formato $Formato = null;

    private static function getFormatPqr(): Formato
    {
        if (!static::$Formato) {
            static::$Formato = Formato::findByAttributes([
                'nombre' => 'pqr'
            ]);
        }
        return static::$Formato;
    }

    /**
     * Obtiene la instancia de la FtPqr o clase que la extienda
     *
     * @param int $documentId
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
     * @return FtPqr
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2021-10-05
     */
    public static function getInstanceForFtId(int $idft): FtPqr
    {
        $className = self::getFormatPqr()->getFtClass();
        return new $className($idft);
    }

    /**
     * Obtiene el nombre de la clase que se usara para el generar el Ws
     *
     * @param Formato $Formato
     * @return IWsHtml
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2023-05-16
     */
    public static function getWebservicePqr(Formato $Formato): IWsHtml
    {
        $className = self::getFormatPqr()->getFtClass();
        return $className::getClassWebservicePqr($Formato);
    }

    public static function notifyAdministrator(string $message, array $log = []): void
    {
        $Logger = GlobalContainer::getLogger();
        $Logger->error($message, $log);

        $EmailSaia = (new EmailSaia())
            ->subject("Error en el modulo de PQR")
            ->htmlWithTemplate($message)
            ->to("soporte@cerok.com", "andres.agudelo@cerok.com");

        (new SendEmailSaia($EmailSaia))->send();
    }

    /**
     * Obtiene la cantidad de tareas y cantidad de tareas finalizadas
     * del documento
     *
     * @param Documento $Documento
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
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
            'total'  => $total
        ];
    }

    /**
     * Retorna la imagen del QR
     *
     * @param FtPqr $FtPqr
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public static function showQr(FtPqr $FtPqr): string
    {
        $qr = $FtPqr->getDocument()->getQR();
        $route = $_SERVER['APP_DOMAIN'] . $qr;

        return '<img src="' . $route . '" width="80px" height="80px" />';
    }

    /**
     * Obtiene la ruta del PDF
     *
     * @param Documento $Documento
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public static function getRoutePdf(Documento $Documento): string
    {
        try {

            if (!$Documento->pdf) {
                $Documento->getPdfJson(true);
            }
            $FileJson = new FileJson($Documento->pdf);
            $FileTemporal = $FileJson->convertToFileTemporal();

            return $_SERVER['APP_DOMAIN'] . $FileTemporal->getRouteFromRoot();

        } catch (Throwable $th) {
            $log = [
                'errorMessage' => $th->getMessage(),
                'iddocumento'  => $Documento->getPK()
            ];
            $message = "No se ha podido generar el pdf del documento con radicado: $Documento->numero (ID:{$Documento->getPK()})";
            self::notifyAdministrator($message, $log);
        }

        return '#';
    }
}
