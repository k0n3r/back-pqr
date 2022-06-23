<?php

namespace App\Bundles\pqr\formatos\pqr_calificacion;


use Saia\core\model\ModelFormat;

class FtPqrCalificacionProperties extends ModelFormat
{
    
    
    public bool $isPDF = false;

    /**
    * @inheritDoc
    */
    protected function defaultDbAttributes(): array
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
    
    /**
    * @inheritDoc
    */
    public function getNumberFolios(): int
    {
        return $this->numero_folios ?? 0;
    }
    
    /**
    * @inheritDoc
    */
    public static function isEnableRadEmail(bool $isRadFormat = false): bool
    {
        return $isRadFormat;
    }
    
    
}