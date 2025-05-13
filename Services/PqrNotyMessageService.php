<?php

namespace App\Bundles\pqr\Services;

use App\services\models\ModelService\ModelService;
use Saia\controllers\functions\Header;
use App\Bundles\pqr\formatos\pqr\FtPqr;
use App\Bundles\pqr\Services\models\PqrNotyMessage;

class PqrNotyMessageService extends ModelService
{

    /**
     * @inheritDoc
     */
    public function getModel(): PqrNotyMessage
    {
        return $this->Model;
    }

    /**
     * Obtiene los registros para actualizar el cuerpo de las notificaciones
     *
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public static function getDataPqrNotyMessages(): array
    {
        $data = [];
        if ($records = PqrNotyMessage::findAllByAttributes([
            'active' => 1,
        ])) {
            foreach ($records as $PqrNotyMessage) {
                $data[] = [
                    'text'  => $PqrNotyMessage->label,
                    'value' => [
                        'id'           => $PqrNotyMessage->getPK(),
                        'description'  => $PqrNotyMessage->description,
                        'subject'      => $PqrNotyMessage->subject,
                        'message_body' => $PqrNotyMessage->message_body,
                        'type'         => $PqrNotyMessage->type,
                    ],
                ];
            }
        }

        return $data;
    }

    /**
     * Resuelve y reemplaza las variables por los valores
     *
     * @param string $baseContent
     * @param FtPqr $FtPqr
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2021
     */
    public static function resolveVariables(
        string $baseContent,
        FtPqr $FtPqr,
    ): string {
        $functions = Header::getFunctionsFromString($baseContent);
        $functions = str_replace(['{*', '*}'], '', $functions);

        foreach ($functions as $variable) {
            $value = call_user_func([self::class, $variable], $FtPqr);
            $baseContent = str_replace(
                "{*$variable*}",
                $value,
                $baseContent,
            );
        }

        return $baseContent;
    }

    /**
     * Obtiene el numero de la PQR
     *
     * @param FtPqr $FtPqr
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2021
     * @see    resolveVariables
     */
    public static function n_radicadoPqr(FtPqr $FtPqr): string
    {
        return $FtPqr->getDocument()->getService()->getFilingReferenceNumber();
    }

    /**
     * Obtiene la etiqueta el formulario PQR
     *
     * @param FtPqr $FtPqr
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2021
     * @see    resolveVariables
     */
    public static function n_nombreFormularioPqr(FtPqr $FtPqr): string
    {
        return $FtPqr->getService()->getPqrForm()->label;
    }

    /**
     * Obtiene la etiqueta el formulario PQR
     *
     * @param FtPqr $FtPqr
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2021
     * @see    resolveVariables
     */
    public static function n_consecutivoPqr(FtPqr $FtPqr): string
    {
        return $FtPqr->getDocument()->numero;
    }
}
