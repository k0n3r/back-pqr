<?php

namespace Saia\Pqr\formatos\pqr;

use Saia\core\model\ModelFormat;

class FtPqrProperties extends ModelFormat
{
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
				'sys_email',
				'sys_estado',
				'sys_tipo' 
            ],
            'date' => [],
            'table' => 'ft_pqr',
            'primary' => 'idft_pqr'
        ];
    }

    protected function defineMoreAttributes()
    {
        return [];
    }
}