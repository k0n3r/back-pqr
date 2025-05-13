<?php

namespace App\Bundles\pqr\Services;

use App\Bundles\pqr\Services\models\PqrForm;
use App\Bundles\pqr\Services\models\PqrNotification;
use App\services\models\ModelService\ModelService;

class PqrNotificationService extends ModelService
{
    /**
     * Obtiene la instancia de PqrNotification actualizada
     *
     * @return PqrNotification
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function getModel(): PqrNotification
    {
        return $this->Model;
    }

    /**
     * Almacena una nueva persona a notificar
     *
     * @param array $attributes
     * @return bool
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function create(array $attributes): bool
    {
        $defaultFields = [
            'email'  => 0,
            'notify' => 1,
        ];
        $attributes = array_merge($defaultFields, $attributes);

        return $this->update($attributes);
    }

    /**
     * Actualiza un registro
     *
     * @param array $attributes
     * @return bool
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2021
     */
    public function update(array $attributes): bool
    {
        if (!isset($attributes['fk_pqr_form'])) {
            $attributes['fk_pqr_form'] = PqrForm::getInstance()->getPK();
        }
        $this->getModel()->setAttributes($attributes);

        return $this->getModel()->save();
    }

    /**
     * Elimina un campo del formulario
     *
     * @return bool
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function delete(): bool
    {
        return $this->getModel()->delete();
    }
}
