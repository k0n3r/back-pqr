<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Service;

use App\Bundles\pqr\Entity\PqrHistory;
use App\Bundles\pqr\helpers\UtilitiesPqr;
use App\Bundles\pqr\Repository\PqrHistoryRepository;
use App\Entity\Funcionario as FuncionarioEntity;
use App\Service\Storage\FileResolver;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Saia\models\Configuracion;
use Saia\models\Dependencia;
use Saia\models\documento\Documento;
use Saia\models\Funcionario;
use Throwable;

class PqrHistoryService
{
    private ?string $logo = null;
    private ?string $customerName = null;

    public function __construct(
        private readonly PqrHistoryRepository $repository,
        private readonly FileResolver $fileResolver,
        private readonly LoggerInterface $logger,
        private readonly EntityManagerInterface $em,
        private readonly string $domain,
    ) {
    }

    public function getRepository(): PqrHistoryRepository
    {
        return $this->repository;
    }

    public function create(array $attributes): PqrHistory
    {
        $entity = new PqrHistory();
        $entity->setIdft((int)$attributes['idft']);
        $entity->setFuncionario(
            $this->em->getReference(FuncionarioEntity::class, (int)$attributes['fk_funcionario']),
        );
        $entity->setTipo((int)$attributes['tipo']);
        $entity->setIdfk((int)($attributes['idfk'] ?? 0));
        $entity->setDescripcion((string)$attributes['descripcion']);
        $entity->setFecha($attributes['fecha'] ?? new DateTimeImmutable());
        $this->repository->create($entity);

        return $entity;
    }

    /**
     * Obtiene los datos de historial para pintar el timeline
     */
    public function getHistoryForTimeline(PqrHistory $history): ?array
    {
        $funcionario = new Funcionario($history->getFkFuncionario());
        $data        = [
            'header'      => true,
            'imgRoute'    => $this->getLogo(),
            'userName'    => $funcionario->getName(),
            'business'    => $this->getCustomerName(),
            'date'        => $history->getFecha()->format('Y-m-d H:i:s'),
            'description' => $history->getDescripcion(),
        ];

        switch ($history->getTipo()) {
            case PqrHistory::TIPO_RESPUESTA:
                $FtPqrRespuesta = UtilitiesPqr::getInstanceForFtIdPqrRespuesta($history->getIdfk());
                $data           = array_merge($data, [
                    'iconPoint'      => 'fa fa-envelope-o',
                    'iconPointColor' => 'warning',
                    'url'            => $this->buildPdfUrl($FtPqrRespuesta->getDocument()),
                ]);
                break;

            case PqrHistory::TIPO_CALIFICACION:
                $FtPqrRespuesta = UtilitiesPqr::getInstanceForFtIdPqrRespuesta($history->getIdfk());
                $data           = array_merge($data, [
                    'iconPoint'      => 'fa fa-comment',
                    'iconPointColor' => 'danger',
                    'description'    => "Se solicita la calificación del servicio prestado a la respuesta # {$FtPqrRespuesta->getDocument()->numero}",
                ]);
                break;

            case PqrHistory::TIPO_CAMBIO_ESTADO:
            case PqrHistory::TIPO_CAMBIO_VENCIMIENTO:
                break;

            case PqrHistory::TIPO_TAREA:
            case PqrHistory::TIPO_NOTIFICACION:
            case PqrHistory::TIPO_ERROR_DIAS_VENCIMIENTO:
            default:
                return null;
        }

        return $data;
    }

    private function buildPdfUrl(Documento $documento): string
    {
        try {
            if (!$documento->pdf) {
                $pdf = $documento->getPdfJson(true);
            } else {
                $pdf = $documento->pdf;
            }

            $publicPath = $this->fileResolver->fromStoragePath($pdf)->getPublicTemporalPath();

            return $this->domain.$publicPath;
        } catch (Throwable $th) {
            $this->logger->error($th->getMessage(), [
                'documentId' => $documento->getPK(),
                'trace'      => $th->getTraceAsString(),
            ]);
        }

        return '#';
    }

    private function getLogo(): ?string
    {
        if ($this->logo !== null) {
            return $this->logo;
        }
        $Configuracion = Configuracion::findByAttributes(['nombre' => 'logo']);
        if (!$Configuracion->getValue()) {
            return null;
        }
        $publicPath = $this->fileResolver->fromStoragePath($Configuracion->getValue())->getPublicTemporalPath();
        $this->logo = $this->domain.$publicPath;

        return $this->logo;
    }

    private function getCustomerName(): ?string
    {
        if ($this->customerName === null) {
            $Dependencia = Dependencia::findByAttributes(['cod_padre' => 0]);
            if ($Dependencia) {
                $this->customerName = $Dependencia->nombre;
            }
        }

        return $this->customerName;
    }
}
