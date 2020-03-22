<?php

namespace Saia\Pqr\formatos\pqr_calificacion;

use Saia\core\model\ModelFormat;

class FtPqrCalificacionProperties extends ModelFormat
{
    public function __construct($id = null)
    {
        parent::__construct($id);
    }

    protected function defaultDbAttributes()
    {
        return [
            'safe' => [
                'campo',
				'dependencia',
				'documento_iddocumento',
				'encabezado',
				'firma',
				'ft_pqr_respuesta',
				'idft_pqr_calificacion' 
            ],
            'date' => [],
            'table' => 'ft_pqr_calificacion',
            'primary' => 'idft_pqr_calificacion'
        ];
    }

    protected function defineMoreAttributes()
    {
        return [];
    }
}