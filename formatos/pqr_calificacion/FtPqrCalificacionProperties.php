<?php

namespace App\Bundles\pqr\formatos\pqr_calificacion;


use Saia\core\model\ModelFormat;
use Saia\models\ruta\RutaFormato;


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
                'documento_iddocumento',
				'encabezado',
				'firma',
				'idft_pqr_calificacion',
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
    
    public function defaultDocumentRoute(): bool
    {
        $RutaFormato = new RutaFormato();
        $RutaFormato->addDefaultRouteFormat(
            $this->getFormat()->getPk(), 
            $this->getDocument()->getPk()
        );

        return true;
    }
        
        /**
    * @inheritDoc
    */
    public function afterRad(): bool
    {
        $this->createTaskFromDataTemp();
        
        return true;
    }
}