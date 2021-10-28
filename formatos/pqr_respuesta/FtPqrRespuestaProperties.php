<?php

namespace App\Bundles\pqr\formatos\pqr_respuesta;


use Saia\core\model\ModelFormat;

class FtPqrRespuestaProperties extends ModelFormat
{
    
    
    public bool $isPDF = false;

    /**
    * @inheritDoc
    */
    protected function defaultDbAttributes(): array
    {
        return [
            'safe' => [
                'idft_pqr_respuesta',
				'documento_iddocumento',
				'encabezado',
				'firma',
				'ft_pqr',
				'dependencia',
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

    /**
    * @inheritDoc
    */
    protected function defineMoreAttributes(): array
    {
        return [];
    }
    
    /**
    * @inheritDoc
    */
    public static function getParamsToAddEdit(int $action, int $idft): array
    {
        return [];
    }
    
    
}