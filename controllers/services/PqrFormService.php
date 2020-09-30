<?php

namespace Saia\Pqr\controllers\services;

use Saia\Pqr\models\PqrForm;
use Saia\Pqr\models\PqrNotyMessage;

class PqrFormService
{

    private PqrForm $PqrForm;

    public function __construct(PqrForm $PqrForm)
    {
        $this->PqrForm = $PqrForm;
    }

    /**
     * Obtiene la instancia de PqrForm actualizada
     *
     * @return PqrForm
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function getPqrForm(): PqrForm
    {
        return $this->PqrForm;
    }

    /**
     * Obtiene los campos del formulario
     *
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function getDataPqrFormFields(): array
    {
        $data = [];
        if ($records = $this->PqrForm->PqrFormFields) {
            foreach ($records as $PqrFormField) {
                $data[] = $PqrFormField->getDataAttributes();
            }
        }
        return $data;
    }

    /**
     * Obtiene los datos de construccion del formulario
     *
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function getDataPqrForm(): array
    {
        return $this->PqrForm->getDataAttributes();
    }

    /**
     * Obtiene los tipos de PQR
     *
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function getTypes(): array
    {
        return $this->PqrForm->getRow('sys_tipo')->getSetting()->options;
    }

    /**
     * Obtiene las notificaciones
     *
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function getDataPqrNotifications(): array
    {
        $data = [];
        if ($records = $this->PqrForm->PqrNotifications) {
            foreach ($records as $PqrNotification) {
                $data[] = $PqrNotification->getDataAttributes();
            }
        }
        return $data;
    }

    /**
     * Obtiene los registros para actualizar el cuerpo de las notificaciones
     *
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function getDataPqrNotyMessages(): array
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
