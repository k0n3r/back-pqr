<?php

namespace App\Bundles\pqr\formatos\pqr_calificacion;


use Saia\core\model\ModelFormat;




class FtPqrCalificacionProperties extends ModelFormat
{
    
    public $isPDF=false;

    protected function defaultDbAttributes()
    {
        return [
            'safe' => [
                'idft_pqr_calificacion',
				'documento_iddocumento',
				'encabezado',
				'firma',
				'dependencia',
				'ft_pqr_respuesta',
				'experiencia_gestion',
				'experiencia_servicio' 
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