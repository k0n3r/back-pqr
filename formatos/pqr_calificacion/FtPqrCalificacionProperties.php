<?php

namespace App\Bundles\pqr\formatos\pqr_calificacion;


use Saia\core\model\ModelFormat;




class FtPqrCalificacionProperties extends ModelFormat
{
    
    public bool $isPDF = false;

    protected function defaultDbAttributes(): array
    {
        return [
            'safe' => [
                'idft_pqr_calificacion',
				'documento_iddocumento',
				'encabezado',
				'firma',
				'ft_pqr_respuesta',
				'dependencia',
				'experiencia_gestion',
				'experiencia_servicio' 
            ],
            'date' => [],
            'table' => 'ft_pqr_calificacion',
            'primary' => 'idft_pqr_calificacion'
        ];
    }

    protected function defineMoreAttributes(): array
    {
        return [];
    }
    
    
}