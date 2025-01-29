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
                'firma',
				'encabezado',
				'documento_iddocumento',
				'idft_pqr_respuesta',
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
        RutaFormato::addDefaultRouteFormat(
            $this->getFormat()->getPK(), 
            $this->getDocument()->getPK()
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