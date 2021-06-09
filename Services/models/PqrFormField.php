<?php

namespace App\Bundles\pqr\Services\models;

use Saia\core\model\Model;
use Saia\models\formatos\CamposFormato;
use App\Bundles\pqr\Services\PqrFormFieldService;

class PqrFormField extends Model
{
    use TModels;

    const ACTIVE = 1;
    const INACTIVE = 0;

    const FIELD_NAME_SYS_TIPO = 'sys_tipo';

    public static ?PqrFormField $PqrFormField_sysTipo = null;

    protected function defineAttributes(): void
    {
        $this->dbAttributes = (object)[
            'safe' => [
                'name',
                'label',
                'required',
                'anonymous',
                'show_report',
                'required_anonymous',
                'setting',
                'fk_pqr_html_field',
                'fk_pqr_form',
                'fk_campos_formato',
                'is_system',
                'orden',
                'active'
            ],
            'primary' => 'id',
            'table' => 'pqr_form_fields'
        ];
    }

    /**
     * @return PqrHtmlField
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2021-05-28
     */
    public function getPqrHtmlField(): PqrHtmlField
    {
        if (!$this->PqrHtmlField) {
            $this->PqrHtmlField = new PqrHtmlField($this->fk_pqr_html_field);
        }

        return $this->PqrHtmlField;
    }

    /**
     * @return CamposFormato
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2021-05-28
     */
    public function getCamposFormato(): CamposFormato
    {
        if (!$this->CamposFormato) {
            $this->CamposFormato = new CamposFormato($this->fk_campos_formato);
        }

        return $this->CamposFormato;
    }

    /**
     * @return PqrForm
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2021-05-28
     */
    public function getPqrForm(): PqrForm
    {
        if (!$this->PqrForm) {
            $this->PqrForm = new PqrForm($this->fk_pqr_form);
        }

        return $this->PqrForm;
    }

    /**
     * Retorna el servicio
     *
     * @return PqrFormFieldService
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2021
     */
    public function getService(): PqrFormFieldService
    {
        return new PqrFormFieldService($this);
    }

    /**
     * obtiene el atributo de setting decodificado
     *
     * @return object
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function getSetting(): object
    {
        return json_decode($this->setting);
    }

    /**
     * Obtiene los campo opciones del campo sys_tipo
     *
     * @return PqrFormField
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2021-06-05
     */
    public static function getSysTipoField(): PqrFormField
    {
        if (!static::$PqrFormField_sysTipo) {
            static::$PqrFormField_sysTipo = self::findByAttributes([
                'name' => PqrFormField::FIELD_NAME_SYS_TIPO
            ]);
        }
        return static::$PqrFormField_sysTipo;
    }

    /**
     * Valida si el campo esta activo
     *
     * @return bool
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2021-06-09
     */
    public function isActive(): bool
    {
        return $this->active == static::ACTIVE;
    }

    /**
     * obtiene los atributos de PqrHtmlField
     * relacionados al registro
     *
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function getFkPqrHtmlField(): array
    {
        return $this->getPqrHtmlField()->getDataAttributes();
    }
}
