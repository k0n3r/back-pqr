<?php

namespace Saia\Pqr\formatos\pqr_respuesta;

use Saia\core\model\ModelFormat;

class FtPqrRespuestaProperties extends ModelFormat
{
    public function __construct($id = null)
    {
        parent::__construct($id);
    }

    protected function defaultDbAttributes()
    {
        return [
            'safe' => [
                'adjuntos',
				'content',
				'dependencia',
				'documento_iddocumento',
				'email',
				'encabezado',
				'firma',
				'fk_response_template',
				'fk_response_template_json',
				'idft_pqr_respuesta' 
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