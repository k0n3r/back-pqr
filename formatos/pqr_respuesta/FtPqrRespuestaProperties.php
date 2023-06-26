<?php

namespace App\Bundles\pqr\formatos\pqr_respuesta;


use Saia\core\model\ModelFormat;
use Saia\models\ruta\RutaFormato;


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
				'ver_copia',
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