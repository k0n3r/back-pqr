<?php

namespace App\Bundles\pqr\formatos\pqr;

use App\services\exception\SaiaException;
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
                'idft_pqr',
				'documento_iddocumento',
				'encabezado',
				'firma',
				'sys_estado',
				'sys_tercero',
				'sys_fecha_vencimiento',
				'sys_fecha_terminado',
				'sys_anonimo',
				'sys_frecuencia',
				'sys_impacto',
				'sys_severidad',
				'sys_oportuno',
				'radicacion',
				'dependencia',
				'sys_tipo',
				'sys_email',
				'sys_folios',
				'sys_anexos',
				'distribucion',
				'destino_interno',
				'select_mensajeria',
				'colilla',
				'digitalizacion' 
            ],
            'date' => ['sys_fecha_vencimiento',
				'sys_fecha_terminado'],
            'table' => 'ft_pqr',
            'primary' => 'idft_pqr'
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
        
        /**
    * @inheritDoc
    */
    public function afterRad(): bool
    {
        $existInPack = PaqueteDocumento::fromPackage($this->getDocument()->getPK());
        if (!$this->radicacion_rapida) {
            $this->postDocumentRad();
            if ($existInPack == 0) {
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
            $Documento->save();
            
            if (!$this->sendDocumentsByEmail()) {
                throw new SaiaException('No fue posible enviar la notificacion por correo');
            }
        }

        return true;
    }
}