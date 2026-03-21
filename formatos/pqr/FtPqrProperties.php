<?php

namespace App\Bundles\pqr\formatos\pqr;

use App\Exception\ValidationFailedException;
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
                'sys_tipo_json',
				'nivel_de_urgenc_json',
				'idft_pqr',
				'firma',
				'encabezado',
				'documento_iddocumento',
				'sys_fecha_terminado',
				'sys_fecha_vencimiento',
				'radicacion',
				'sys_frecuencia',
				'sys_anonimo',
				'sys_impacto',
				'sys_oportuno',
				'sys_severidad',
				'sys_tercero',
				'sys_estado',
				'dependencia',
				'sys_tipo',
				'fecha_del_event',
				'nombre',
				'n_mero_de_ident',
				'sys_email',
				'ciudad',
				'edad',
				'ciudad_1',
				'sys_dependencia',
				'descripcion_1',
				'nivel_de_urgenc',
				'sys_folios',
				'sys_anexos',
				'sys_tratamiento',
				'distribucion',
				'destino_interno',
				'select_mensajeria',
				'descripcion',
				'colilla',
				'digitalizacion'
            ],
            'date' => ['sys_fecha_terminado',
				'sys_fecha_vencimiento',
				'fecha_del_event'],
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
                throw new ValidationFailedException('No fue posible enviar la notificacion por correo');
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
          throw new ValidationFailedException('No fue posible editar la distribución');
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
            throw new ValidationFailedException('No fue posible enviar la notificacion por correo');
        }
    }

    return true;
}
}