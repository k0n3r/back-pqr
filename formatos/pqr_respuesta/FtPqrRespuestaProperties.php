<?php

namespace App\Bundles\pqr\formatos\pqr_respuesta;


use Saia\core\model\ModelFormat;




class FtPqrRespuestaProperties extends ModelFormat
{
    
    public $isPDF=false;

    protected function defaultDbAttributes()
    {
        return [
            'safe' => [
                'idft_pqr_respuesta',
				'documento_iddocumento',
				'encabezado',
				'firma',
				'dependencia',
				'ft_pqr',
				'ciudad_origen',
				'destino',
				'tipo_distribucion',
				'copia',
				'asunto',
				'contenido',
				'despedida',
				'otra_despedida',
				'anexos_digitales',
				'anexos_fisicos',
				'copia_interna',
				'sol_encuesta' 
            ],
            'date' => [],
            'table' => 'ft_pqr_respuesta',
            'primary' => 'idft_pqr_respuesta'
        ];
    }

    protected function defineMoreAttributes()
    {
        return [];
    }
    
    
}