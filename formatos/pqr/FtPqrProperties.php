<?php

namespace App\Bundles\pqr\formatos\pqr;

use Saia\core\model\ModelFormat;
use App\Exception\ValidationFailedException;
use Saia\controllers\distribucion\DistributionExecutor;
use Saia\models\documento\Documento;

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
                'sys_frecuencia',
                'sys_fecha_vencimiento',
                'radicacion',
                'sys_fecha_terminado',
                'sys_estado',
                'sys_anonimo',
                'dependencia',
                'sys_tipo',
                'sys_email',
                'sys_folios',
                'descripcion_1',
                'sys_anexos',
                'linea',
                'distribucion',
                'destino_interno',
                'select_mensajeria',
                'descripcion',
                'colilla',
                'digitalizacion'
            ],
            'date' => ['sys_fecha_vencimiento', 'sys_fecha_terminado'],
            'table' => 'ft_pqr',
            'primary' => 'idft_pqr'
        ];
    }

    /**
     * @inheritDoc
     */
    public function afterEdit(): bool
    {
        $Documento = $this->getDocument();

        if (!$this->editDistribution()) {
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

    /**
     * @inheritDoc
     */
    public function afterRad(): bool
    {
        $this->createTaskFromDataTemp();
        if (!$this->radicacion_rapida) {
            $this->postDocumentRad();
            if (!$this->sendDocumentsByEmail()) {
                throw new ValidationFailedException('No fue posible enviar la notificacion por correo');
            }
        }

        return true;
    }
}
