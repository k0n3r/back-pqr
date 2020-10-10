<?php

namespace Saia\Pqr\models;

use Saia\core\model\Model;
use Saia\models\formatos\CamposFormato;
use Saia\Pqr\models\PqrHtmlField;

class PqrFormField extends Model
{
    use TModel;

    protected function defineAttributes(): void
    {
        $this->dbAttributes = (object) [
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
            'table' => 'pqr_form_fields',
            'relations' => [
                'PqrHtmlField' => [
                    'model' => PqrHtmlField::class,
                    'attribute' => 'id',
                    'primary' => 'fk_pqr_html_field',
                    'relation' => self::BELONGS_TO_ONE
                ],
                'PqrForm' => [
                    'model' => PqrForm::class,
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
        return $this->PqrHtmlField->getDataAttributes();
    }
}
