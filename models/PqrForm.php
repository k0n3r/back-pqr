<?php

namespace Saia\Pqr\models;

use Saia\models\Contador;
use Saia\core\model\Model;
use Saia\models\formatos\Formato;
use Saia\Pqr\models\PqrFormField;
use Saia\Pqr\models\PqrNotification;

class PqrForm extends Model
{
    use TModel;

    const NOMBRE_REPORTE_PENDIENTE = 'rep_pendientes_pqr';
    const NOMBRE_REPORTE_PROCESO = 'rep_proceso_pqr';
    const NOMBRE_REPORTE_TERMINADO = 'rep_terminados_pqr';

    protected function defineAttributes(): void
    {
        $this->dbAttributes = (object) [
            'safe' => [
                'fk_formato',
                'fk_contador',
                'label',
                'name',
                'show_anonymous',
                'show_label',
                'rad_email',
                'active'
            ],
            'primary' => 'id',
            'table' => 'pqr_forms',
            'relations' => [
                'Formato' => [
                    'model' => Formato::class,
                    'attribute' => 'idformato',
                    'primary' => 'fk_formato',
                    'relation' => self::BELONGS_TO_ONE
                ],
                'Contador' => [
                    'model' => Contador::class,
                    'attribute' => 'idcontador',
                    'primary' => 'fk_contador',
                    'relation' => self::BELONGS_TO_ONE
                ],
                'PqrFormFields' => [
                    'model' => PqrFormField::class,
                    'attribute' => 'fk_pqr_form',
                    'primary' => 'id',
                    'relation' => self::BELONGS_TO_MANY,
                    'order' => 'orden ASC'
                ],
                'PqrNotifications' => [
                    'model' => PqrNotification::class,
                    'attribute' => 'fk_pqr_form',
                    'primary' => 'id',
                    'relation' => self::BELONGS_TO_MANY
                ]
            ]
        ];
    }

    /**
     * Cuenta la cantidad de campos que tiene el formulario
     *
     * @return integer
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function countFields(): int
    {
        $fields = $this->PqrFormFields;

        return $fields ? count($fields) : 0;
    }


    /**
     * obtiene la instancia del modelo PqrForm activa
     *
     * @return PqrForm|null
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public static function getPqrFormActive(): ?PqrForm
    {
        return PqrForm::findByAttributes(['active' => 1]);
    }


    public function getRow(string $name): PqrFormField
    {
        foreach ($this->PqrFormFields as $PqrFormField) {
            if ($PqrFormField->name == $name) {
                return $PqrFormField;
            }
        }
    }
}
