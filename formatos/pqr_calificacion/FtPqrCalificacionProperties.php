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
    public function getNumberFolios(): int
    {
        $Documento = $this->getDocument();

        if ($Documento->numero_folios) {
            $total = $Documento->numero_folios;
        } else {
            $total = ($this->numero_folios ?? 0);
        }

        return (int)$total;
    }
        
    
}