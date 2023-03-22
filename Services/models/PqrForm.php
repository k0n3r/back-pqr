<?php

namespace App\Bundles\pqr\Services\models;

use App\Bundles\pqr\Services\controllers\WebservicePqr;
use App\services\exception\SaiaException;
use Saia\core\model\Model;
use Saia\models\formatos\CamposFormato;
use Saia\models\formatos\Formato;
use App\Bundles\pqr\Services\PqrFormService;

class PqrForm extends Model
{
    private ?Formato $Formato = null;
    use TModels;

    const NOMBRE_REPORTE_PENDIENTE = 'rep_pendientes_pqr';
    const NOMBRE_REPORTE_PROCESO = 'rep_proceso_pqr';
    const NOMBRE_REPORTE_TERMINADO = 'rep_terminados_pqr';
    const NOMBRE_REPORTE_TODOS = 'rep_todos_pqr';
    const NOMBRE_REPORTE_POR_DEPENDENCIA = 'rep_dependencia_pqr';
    const NOMBRE_PANTALLA_GRAFICO = 'PQRSF';
    const FILTER_TODOS = 'dep_todos';
    const FILTER_PENDIENTES = 'dep_pendientes';
    const FILTER_RESUELTAS = 'dep_resueltas';


    private static ?PqrForm $PqrForm = null;

    protected function defineAttributes(): void
    {
        $this->dbAttributes = (object)[
            'safe'    => [
                'fk_formato',
                'fk_contador',
                'label',
                'name',
                'show_anonymous',
                'show_label',
                'show_empty',
                'active',
                'response_configuration',
                'fk_field_time',
                'enable_filter_dep'
            ],
            'primary' => 'id',
            'table'   => 'pqr_forms'
        ];
    }

    /**
     * @return Formato
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2021-05-28
     */
    public function getFormatoFk(): Formato
    {
        if (!$this->Formato) {
            $this->Formato = new Formato($this->fk_formato);
        }
        return $this->Formato;
    }

    /**
     * Obtiene los campos del formato
     *
     * @return PqrFormField[]
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2021-05-28
     */
    public function getPqrFormFields(): array
    {
        return PqrFormField::findAllByAttributes([
            'fk_pqr_form' => $this->getPK()
        ], [], 'orden ASC');
    }

    /**
     * Obtiene los campos del formato
     *
     * @return PqrNotification[]
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2021-05-28
     */
    public function getPqrNotifications(): array
    {
        return PqrNotification::findAllByAttributes([
            'fk_pqr_form' => $this->getPK()
        ]);
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
        $fields = $this->getPqrFormFields();

        return $fields ? count($fields) : 0;
    }


    /**
     * Obtiene la instancia del formulario activo
     *
     * @return PqrForm
     * @author Andres Agudelo <andres.agudelo@cerok.com> @date 2021-02-13
     */
    public static function getInstance(): PqrForm
    {
        if (!self::$PqrForm) {
            $rows = PqrForm::findAllByAttributes(['active' => 1]);
            if (count($rows) != 1) {
                throw new SaiaException("No se encontro un formulario activo");
            }
            self::$PqrForm = $rows[0];
        }

        return self::$PqrForm;
    }

    /**
     * @return WebservicePqr
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2023-03-22
     */
    public function getWebservicePqr(): WebservicePqr
    {
        return new WebservicePqr($this->getFormatoFk());
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
        foreach ($this->getPqrFormFields() as $PqrFormField) {
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

    /**
     * Obtiene la instancia de CamposFormato sobre el cual
     * se valida el tiempo de respuesta
     *
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2021-06-06
     */
    public function getCampoFormatoForFieldTime(): CamposFormato
    {
        return new CamposFormato($this->fk_field_time);
    }
}
