<?php

namespace App\Bundles\pqr\formatos\pqr_calificacion;


use Saia\core\model\ModelFormat;




class FtPqrCalificacionProperties extends ModelFormat
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
				'experiencia_gestion',
				'experiencia_servicio',
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