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
                'anexos',
				'autocompletar_munici',
				'dependencia',
				'documento_iddocumento',
				'email',
				'encabezado',
				'firma',
				'idft_pqr',
				'linea',
				'listado',
				'multiple',
				'numerico',
				'radio',
				'sys_dependencia',
				'sys_email',
				'sys_estado',
				'sys_tipo',
				'sys_tratamiento' 
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