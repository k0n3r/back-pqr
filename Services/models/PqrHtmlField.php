<?php

namespace App\Bundles\pqr\Services\models;

use Saia\core\model\Model;

class PqrHtmlField extends Model
{
    use TModels;

    public const string TYPE_DEPENDENCIA = 'dependencia';
    public const string TYPE_LOCALIDAD = 'localidad';

    protected function defineAttributes(): void
    {
        $integer = [
            'uniq',
            'active',
        ];
        $this->dbAttributes = (object)[
            'safe'    => array_merge($integer, [
                'label',
                'type',
                'type_saia',
            ]),
            'integer' => $integer,
            'primary' => 'id',
            'table'   => 'pqr_html_fields',
        ];
    }

    /**
     * Valida si el tipo de campo es valido
     * para guardar como dias de respuesta o balanceo
     *
     * @return bool
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2021-06-05
     */
    public function isValidFieldForResponseDaysOrBalance(): bool
    {
        $allow = [
            'Select',
            'Radio',
        ];

        return in_array($this->type_saia, $allow);
    }

    /**
     * Valida si el campo ingresa datos en campo opciones
     *
     * @return bool
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2021-06-05
     */
    public function isValidForOptions(): bool
    {
        $allowOptions = [
            'Select',
            'Radio',
            'Checkbox',
        ];

        return in_array($this->type_saia, $allowOptions);
    }

}
