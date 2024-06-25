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
                'documento_iddocumento',
				'encabezado',
				'firma',
				'idft_pqr_respuesta',
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
				'sol_encuesta',
				'cerrar_tareas' 
            ],
            'date' => [],
            'table' => 'ft_pqr_respuesta',
            'primary' => 'idft_pqr_respuesta'
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
        
    
}