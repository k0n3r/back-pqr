<?php

namespace App\Bundles\pqr\Services;

use App\Bundles\pqr\Services\models\PqrNotyMessage;

class PqrNotyMessageService
{

    private PqrNotyMessage $PqrNotyMessage;
    private string $errorMessage;

    public function __construct(PqrNotyMessage $PqrNotyMessage)
    {
        $this->PqrNotyMessage = $PqrNotyMessage;
    }


    /**
     * Retorna el mensaje de error
     *
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2021
     */
    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }

    /**
     * Obtiene la instancia de PqrNotyMessage actualizada
     *
     * @return PqrNotyMessage
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function getModel(): PqrNotyMessage
    {
        return $this->PqrNotyMessage;
    }

    /**
     * Actualiza un registro
     * 
     * @param array $data
     * @return boolean
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2021
     */
    public function update(array $data): bool
    {
        $this->PqrNotyMessage->setAttributes($data);

        return $this->PqrNotyMessage->save();
    }


    /**
     * Obtiene los registros para actualizar el cuerpo de las notificaciones
     *
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public static function getDataPqrNotyMessages(): array
    {
        $data = [];
        if ($records = PqrNotyMessage::findAllByAttributes([
            'active' => 1
        ])) {
            foreach ($records as $PqrNotyMessage) {
                $data[] = [
                    'text' => $PqrNotyMessage->label,
                    'value' => [
                        'id' => $PqrNotyMessage->getPK(),
                        'description' => $PqrNotyMessage->description,
                        'subject' => $PqrNotyMessage->subject,
                        'message_body' => $PqrNotyMessage->message_body,
                        'type' => $PqrNotyMessage->type
                    ]
                ];
            }
        }

        return $data;
    }
}
