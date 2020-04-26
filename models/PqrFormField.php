<?php

namespace Saia\Pqr\Models;

use Saia\core\model\Model;
use Saia\models\formatos\CamposFormato;
use Saia\Pqr\Models\PqrHtmlField;

class PqrFormField extends Model
{

    public function __construct($id = null)
    {
        parent::__construct($id);
    }

    protected function defineAttributes(): void
    {
        $this->dbAttributes = (object) [
            'safe' => [
                'name',
                'label',
                'required',
                'show_anonymous',
                'setting',
                'fk_pqr_html_field',
                'fk_campos_formato',
                'system',
                'orden',
                'fk_pqr_form',
                'active'
            ],
            'primary' => 'id',
            'table' => 'pqr_form_fields',
            'relations' => [
                'PqrHtmlField' => [
                    'model' => PqrHtmlField::class,
                    'attribute' => 'id',
                    'primary' => 'fk_pqr_html_field',
                    'relation' => self::BELONGS_TO_ONE
                ],
                'PqrForm' => [
                    'model' => PqrHtmlField::class,
                    'attribute' => 'id',
                    'primary' => 'fk_pqr_form',
                    'relation' => self::BELONGS_TO_ONE
                ],
                'CamposFormato' => [
                    'model' => CamposFormato::class,
                    'attribute' => 'idcampos_formato',
                    'primary' => 'fk_campos_formato',
                    'relation' => self::BELONGS_TO_ONE
                ]
            ]
        ];
    }

    /**
     * obtiene los datos de las atributos
     *
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function getDataAttributes(): array
    {
        $attributes = $this->getSafeAttributes();
        array_push($attributes, $this->getPkName());

        $data = [];
        foreach ($attributes as $value) {

            $Stringy = new \Stringy\Stringy("get_{$value}");
            $method = (string) $Stringy->upperCamelize();
            $data[$value] = (method_exists($this, $method)) ? $this->$method() : $this->$value;
        }

        return $data;
    }

    /**
     * obtiene el atributo de setting decodificado 
     *
     * @return object
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function getSetting(): object
    {
        return json_decode($this->setting);
    }

    /**
     * obtiene los atributos de PqrHtmlField
     * relacionados al registro
     *
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function getFkPqrHtmlField(): array
    {
        return $this->PqrHtmlField->getAttributes();
    }
}
