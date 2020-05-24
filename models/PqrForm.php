<?php

namespace Saia\Pqr\models;

use Saia\core\model\Model;
use Saia\models\Contador;
use Saia\models\formatos\Formato;
use Saia\Pqr\models\PqrFormField;

class PqrForm extends Model
{

    /**
     * almacena las instancias activas
     *
     * @var array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public array $pqrFormFieldsActive = [];

    public function __construct($id = null)
    {
        parent::__construct($id);
    }

    protected function defineAttributes(): void
    {
        $this->dbAttributes = (object) [
            'safe' => [
                'fk_formato',
                'fk_contador',
                'show_anonymous',
                'show_label',
                'label',
                'name',
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
                ]
            ]
        ];
    }

    /**
     * obtiene las instancias activas del modelo PqrFormField
     *
     * @param boolean $force : true => forza a consultar nuevamente
     * @return PqrFormField[]
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function getPqrFormFieldsActive(bool $force = false): array
    {
        if (!$this->pqrFormFieldsActive || $force) {
            $data = [];
            foreach ($this->PqrFormFields as $PqrFormFields) {
                if ($PqrFormFields->active) {
                    $data[] = $PqrFormFields;
                }
            }
            $this->pqrFormFieldsActive = $data;
        }

        return $this->pqrFormFieldsActive;
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
     * Obtiene los valores de los campos que conforman el 
     * formulario
     *
     * @param integer $type => 1: Solo campos Activos, 2: Todos
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function getAttributesFormFields(int $type = 2): array
    {
        switch ($type) {
            case 1: //active
                $instances = $this->getPqrFormFieldsActive(true);
                break;
            default: //all
                $instances = $this->PqrFormFields;
                break;
        }

        $data = [];
        foreach ($instances as $PqrFormField) {
            $data[] = $PqrFormField->getDataAttributes();
        }

        return $data;
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
}
