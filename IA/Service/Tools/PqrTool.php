<?php

declare(strict_types=1);

namespace App\Bundles\pqr\IA\Service\Tools;

use App\Bundles\pqr\helpers\UtilitiesPqr;
use InvalidArgumentException;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Saia\controllers\SaveDocument;
use Saia\models\Configuracion;
use Saia\models\formatos\CamposFormato;
use Saia\models\formatos\Formato;
use Saia\models\vistas\VfuncionarioDc;
use Symfony\AI\Agent\Toolbox\Attribute\AsTool;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Throwable;

#[AsTool(
    name: 'create_response_pqr',
    description: 'Registra una respuesta oficial a una PQR en el sistema. Parámetros requeridos: documentId, contentAnswers (texto completo de la respuesta), subject (asunto).',
    method: 'createResponsePQR',
)]
readonly class PqrTool
{
    public function __construct(
        #[Autowire(service: 'monolog.logger.ia')]
        private LoggerInterface $logger,
        private Security $security,
        #[Autowire(service: 'cache.app')]
        private CacheItemPoolInterface $cache,
    ) {}

    /**
     * @param int $documentId ID del documento PQR al que se responde
     * @param string $contentAnswers Contenido de la respuesta redactada
     * @param string $subject Asunto de la respuesta redactada
     */
    public function createResponsePQR(
        int $documentId,
        string $contentAnswers,
        string $subject,
    ): string {
        try {
            $format = $this->getFormatAnswer();
            $ftPqr = UtilitiesPqr::getInstanceForDocumentId($documentId);

            $userId = $this->security->getUser()->getId();

            $vfuncionarioDc = VfuncionarioDc::getActiveRoles(userId: $userId)[0];

            $data = array_merge($this->getDefaultData(), [
                'asunto'            => $subject,
                'contenido'         => $contentAnswers,
                'formatId'          => $format->getPK(),
                'tipo_radicado'     => $format->getCounter()->getPK(),
                'ft_pqr'            => $ftPqr->getPK(),
                'destino'           => $ftPqr->sys_tercero,
                'dependencia'       => $vfuncionarioDc->iddependencia_cargo,
                'tipo_distribucion' => $this->getDefaultTipoDistribucion($format->getField('tipo_distribucion')),
                'despedida'         => $this->getDefaultDespedida($format->getField('despedida')),
            ]);

            $SaveDocument = new SaveDocument($format, $vfuncionarioDc);
            $SaveDocument->createOrUpdateDocument($data);
            $documentIdAnswer = $SaveDocument->getDocument()->getPK();

            return "La respuesta ha sido creada exitosamente. [open_document:$documentIdAnswer]";
        } catch (Throwable $exception) {
            $this->logger->error($exception->getMessage(), [
                'trace'          => $exception->getTrace(),
                'documentId'     => $documentId,
                'contentAnswers' => $contentAnswers,
            ]);

            return "Hubo un error al crear la respuesta, por favor informe al administrador del sistema. Error: {$exception->getMessage()}";
        }
    }

    private function getDefaultData(): array
    {
        return [
            'ciudad_origen'   => $this->getDefaultCiudad(),
            'ver_copia'       => 1,
            'sol_encuesta'    => 1,
            'cerrar_tareas'   => 1,
            'webservice'      => 0,
            'canal_recepcion' => 'FÍSICO',

        ];
    }

    private function getDefaultCiudad(): int
    {
        return (int)Configuracion::getConfigForName('ciudad')['valor'];
    }

    private function getFormatAnswer(): Formato
    {
        return Formato::findByAttributes([
            'nombre' => 'pqr_respuesta',
        ]);
    }

    private function getDefaultTipoDistribucion(CamposFormato $campoFormato): int
    {
        $item = $this->cache->getItem('pqr_tool.tipo_distribucion');

        if ($item->isHit()) {
            return $item->get();
        }

        $records = $campoFormato->getCampoOpciones([
            'llave' => 4,
        ]);

        if (!$records) {
            throw new InvalidArgumentException("No se encontro el tipo de distribucion");
        }

        $value = $records[0]->getPK();
        $this->cache->save($item->set($value));

        return $value;
    }

    private function getDefaultDespedida(CamposFormato $campoFormato): int
    {
        $item = $this->cache->getItem('pqr_tool.despedida');

        if ($item->isHit()) {
            return $item->get();
        }

        $records = $campoFormato->getCampoOpciones([
            'llave' => 1,
        ]);

        if (!$records) {
            throw new InvalidArgumentException("No se encontro la información de despedida");
        }

        $value = $records[0]->getPK();
        $this->cache->save($item->set($value));

        return $value;
    }

}
