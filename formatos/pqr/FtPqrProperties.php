<?php

namespace Saia\Pqr\formatos\pqr;

use Saia\core\model\ModelFormat;

class FtPqrProperties extends ModelFormat
{
    public $isPDF=false;

    public function __construct($id = null)
    {
        parent::__construct($id);
    }

    protected function defaultDbAttributes()
    {
        return [
            'safe' => [
                'dependencia',
				'documento_iddocumento',
				'encabezado',
				'firma',
				'idft_pqr',
				'linea',
				'sys_anonimo',
				'sys_email',
				'sys_estado',
				'sys_fecha_terminado',
				'sys_fecha_vencimiento',
				'sys_tipo',
				'sys_tratamiento' 
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