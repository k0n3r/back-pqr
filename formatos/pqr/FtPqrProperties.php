<?php

namespace App\Bundles\pqr\formatos\pqr;


use Saia\core\model\ModelFormat;




class FtPqrProperties extends ModelFormat
{
    
    public $isPDF=false;

    protected function defaultDbAttributes()
    {
        return [
            'safe' => [
                'idft_pqr',
				'documento_iddocumento',
				'encabezado',
				'firma',
				'sys_estado',
				'sys_tercero',
				'sys_fecha_vencimiento',
				'sys_fecha_terminado',
				'sys_anonimo',
				'dependencia',
				'sys_tipo',
				'sys_email' 
            ],
            'date' => ['sys_fecha_vencimiento',
				'sys_fecha_terminado'],
            'table' => 'ft_pqr',
            'primary' => 'idft_pqr'
        ];
    }

    protected function defineMoreAttributes()
    {
        return [];
    }
    
    
}