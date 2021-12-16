<?php

namespace App\Bundles\pqr\formatos\pqr;


use Saia\core\model\ModelFormat;

class FtPqrProperties extends ModelFormat
{


    public bool $isPDF = false;

    /**
     * @inheritDoc
     */
    protected function defaultDbAttributes(): array
    {
        return [
            'safe'    => [
                'idft_pqr',
                'documento_iddocumento',
                'sys_estado',
                'sys_tercero',
                'sys_fecha_vencimiento',
                'sys_fecha_terminado',
                'sys_anonimo',
                'sys_frecuencia',
                'sys_impacto',
                'sys_severidad',
                'dependencia',
                'sys_tipo',
                'sys_email',
                'encabezado',
                'sys_anexos',
                'firma'
            ],
            'date'    => [
                'sys_fecha_vencimiento',
                'sys_fecha_terminado'
            ],
            'table'   => 'ft_pqr',
            'primary' => 'idft_pqr'
        ];
    }

    /**
     * @inheritDoc
     */
    protected function defineMoreAttributes(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public static function getParamsToAddEdit(int $action, int $idft): array
    {
        return [];
    }


}