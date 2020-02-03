<?php

namespace Saia\Pqr\formatos\pqrsf;

use Saia\core\model\ModelFormat;

class FtPqrsfProperties extends ModelFormat
{
    public function __construct($id = null)
    {
        parent::__construct($id);
    }

    protected function defaultDbAttributes()
    {
        return [
            'safe' => [
                'area_de_texto_obliga',
				'area_de_texto_opcion',
				'checkbox_obligatorio',
				'checkbox_opcional',
				'cuadro_de_texto_obli',
				'cuadro_de_texto_opci',
				'dependencia',
				'documento_iddocumento',
				'encabezado',
				'firma',
				'idft_pqrsf',
				'lista_obligatorio',
				'lista_opcional',
				'nombre',
				'radio_obligatorio',
				'radio_opcional' 
            ],
            'date' => [],
            'table' => 'ft_pqrsf',
            'primary' => 'idft_pqrsf'
        ];
    }

    protected function defineMoreAttributes()
    {
        return [];
    }
}