<?php

namespace App\Bundles\pqr\formatos\pqr;

use App\Exception\SaiaException;
use Saia\controllers\distribucion\DistributionExecutor;
use Saia\models\documento\Documento;
use Saia\models\radicacion_masiva\PaqueteDocumento;

use Saia\core\model\ModelFormat;
use Saia\models\ruta\RutaFormato;


class FtPqrProperties extends ModelFormat
{
    use DistributionExecutor;
    
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
				'idft_pqr',
				'sys_tercero',
				'sys_severidad',
				'sys_oportuno',
				'sys_impacto',
				'radicacion',
				'sys_frecuencia',
				'sys_fecha_vencimiento',
				'sys_anonimo',
				'sys_fecha_terminado',
				'sys_estado',
				'dependencia',
				'sys_tipo',
				'sys_email',
				'sys_folios',
				'sys_anexos',
				'distribucion',
				'destino_interno',
				'select_mensajeria',
				'descripcion',
				'colilla',
				'digitalizacion' 
            ],
            'date' => ['sys_fecha_vencimiento',
				'sys_fecha_terminado'],
            'table' => 'ft_pqr',
            'primary' => 'idft_pqr'
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
        if (!$this->radicacion_rapida) {
            $this->postDocumentRad();
            if (!$this->getDocument()->belongsToPackage()) {
                if (!$this->sendDocumentsByEmail()) {
                    throw new SaiaException('No fue posible enviar la notificacion por correo');
                }
            }
            
        }

        return true;
    }

    /**
    * @inheritDoc
    */
    public function afterEdit(): bool
    {
         $Documento = $this->getDocument();
         
        if (!$this->editDistribution()){
              throw new SaiaException('No fue posible editar la distribución');
        }
        
        if (
            $Documento->isStarted() &&
            $this->getFormat()->isAutoApprove()
        ) {
            $Documento->estado = Documento::APROBADO;
            $Documento->estado_aprobacion = Documento::APROBADO_LABEL;
            $Documento->save();
            $Documento->getPdfJson(true);
            
            if (!$this->sendDocumentsByEmail()) {
                throw new SaiaException('No fue posible enviar la notificacion por correo');
            }
        }

        return true;
    }
}