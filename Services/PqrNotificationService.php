<?php

namespace App\Bundles\pqr\Services;

use App\Bundles\pqr\Services\models\PqrForm;
use App\Bundles\pqr\Services\models\PqrNotification;
use App\services\models\ModelService;

class PqrNotificationService extends ModelService
{
        /**
     * Obtiene la instancia de PqrNotification actualizada
     *
     * @return PqrNotification
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function getModel(): PqrNotification
    {
        return $this->Model;
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
            $data['fk_pqr_form'] = PqrForm::getInstance()->getPK();
        }
        $this->getModel()->setAttributes($data);

        return $this->getModel()->save();
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
        return $this->getModel()->delete();
    }
}
