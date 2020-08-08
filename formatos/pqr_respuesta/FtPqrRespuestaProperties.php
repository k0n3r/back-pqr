<?php

namespace Saia\Pqr\formatos\pqr_respuesta;

use Saia\core\model\ModelFormat;

class FtPqrRespuestaProperties extends ModelFormat
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
                'anexos_digitales',
				'anexos_fisicos',
				'asunto',
				'ciudad_origen',
				'contenido',
				'copia',
				'copia_interna',
				'dependencia',
				'despedida',
				'destino',
				'documento_iddocumento',
				'encabezado',
				'firma',
				'ft_pqr',
				'idft_pqr_respuesta',
				'otra_despedida',
				'sol_encuesta',
				'tipo_distribucion' 
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