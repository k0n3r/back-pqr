<?php

namespace App\Bundles\pqr\Services;

use App\Bundles\pqr\Services\models\PqrForm;
use App\Bundles\pqr\Services\models\PqrNotification;

class PqrNotificationService
{

    private PqrNotification $PqrNotification;
    private string $errorMessage;

    public function __construct(PqrNotification $PqrNotification)
    {
        $this->PqrNotification = $PqrNotification;
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
     * Obtiene la instancia de PqrNotification actualizada
     *
     * @return PqrNotification
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function getModel(): PqrNotification
    {
        return $this->PqrNotification;
    }

    /**
     * Almacena una nueva persona a notificar
     *
     * @return boolean
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function create(array $data): bool
    {
        $defaultFields = [
            'email' => 0,
            'notify' => 1
        ];
        $attributes = array_merge($defaultFields, $data);

        return $this->update($attributes);
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
        if (!isset($data['fk_pqr_form'])) {
            $data['fk_pqr_form'] = PqrForm::getPqrFormActive()->getPK();
        }
        $this->PqrNotification->setAttributes($data);

        return $this->PqrNotification->save();
    }

    /**
     * Elimina un campo del formulario
     *
     * @return boolean
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function delete(): bool
    {
        return $this->PqrNotification->delete();
    }
}
