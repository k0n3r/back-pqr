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
                'area_de_texto',
				'checkbox',
				'cuadro_de_texto',
				'dependencia',
				'documento_iddocumento',
				'email',
				'encabezado',
				'firma',
				'idft_pqr',
				'lista_desplegable',
				'radio',
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