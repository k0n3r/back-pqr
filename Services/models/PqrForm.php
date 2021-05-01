<?php

namespace App\Bundles\pqr\Services\models;

use Exception;
use Saia\models\Contador;
use Saia\core\model\Model;
use Saia\models\formatos\Formato;
use App\Bundles\pqr\Services\PqrFormService;

class PqrForm extends Model
{
    use TModels;

    const NOMBRE_REPORTE_PENDIENTE = 'rep_pendientes_pqr';
    const NOMBRE_REPORTE_PROCESO = 'rep_proceso_pqr';
    const NOMBRE_REPORTE_TERMINADO = 'rep_terminados_pqr';
    const NOMBRE_REPORTE_TODOS = 'rep_todos_pqr';
    const NOMBRE_PANTALLA_GRAFICO = 'PQRSF';

    private static ?PqrForm $PqrForm = null;

    protected function defineAttributes(): void
    {
        $this->dbAttributes = (object)[
            'safe' => [
                'fk_formato',
                'fk_contador',
                'label',
                'name',
                'show_anonymous',
                'show_label',
                'rad_email',
                'active',
                'response_configuration'
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
     * Retorna el servicio
     *
     * @return PqrFormService
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2021
     */
    public function getService(): PqrFormService
    {
        return new PqrFormService($this);
    }

    /**
     * Cuenta la cantidad de campos que tiene el formulario
     *
     * @return integer
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function countFields(): int
    {
        $fields = $this->PqrFormFields;

        return $fields ? count($fields) : 0;
    }


    /**
     * Obtiene la instancia del formulario activo
     *
     * @return PqrForm
     * @throws Exception
     * @author Andres Agudelo <andres.agudelo@cerok.com> @date 2021-02-13
     */
    public static function getInstance(): PqrForm
    {
        if (!self::$PqrForm) {
            $rows = PqrForm::findAllByAttributes(['active' => 1]);
            if (count($rows) != 1) {
                throw new Exception("No se encontro un formulario activo");
            }
            self::$PqrForm = $rows[0];
        }

        return self::$PqrForm;
    }

    /**
     * Obtiene el campo de la pqr segun el nombre
     *
     * @param string $name
     * @return null|PqrFormField
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function getRow(string $name): ?PqrFormField
    {
        foreach ($this->PqrFormFields as $PqrFormField) {
            if ($PqrFormField->name == $name) {
                return $PqrFormField;
            }
        }
        return null;
    }

    /**
     * Obtiene decodificada la configuracion
     * de la respuesta
     *
     * @param boolean $inArray : Retorna como array
     * @return null|object|array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function getResponseConfiguration(bool $inArray = false)
    {
        return json_decode($this->response_configuration, $inArray);
    }
}
