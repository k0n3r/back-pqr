<?php

declare(strict_types=1);

namespace App\Bundles\pqr\IA\Service\Tools;

use App\Bundles\pqr\helpers\UtilitiesPqr;
use App\Bundles\pqr\IA\Service\PqrIaGuard;
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
    description: <<<'DESC'
        Registra una respuesta oficial a una PQR en el sistema.

        ### Cuándo usarla
        Cuando el administrador solicite redactar o registrar una respuesta a una PQR. No la invoques sin solicitud explícita.

        ### Estructura de la base de conocimiento (KB) para PQR
        Cada chunk de la KB expone estos metadatos:
        - `Documento ID` (`_documentId`): ID del documento que generó ese chunk. Puede ser la PQR original, una respuesta o un anexo.
        - `Documento Raíz ID` (`_rootDocumentId`): ID de la PQR original. Todas las respuestas y anexos asociados comparten este mismo valor.

        **Regla clave**: el `documentId` que debes pasar a `create_response_pqr` es siempre el `Documento Raíz ID` (`_rootDocumentId`). Nunca uses el `Documento ID` de una respuesta o anexo.

        ### Flujo para redactar y registrar una respuesta
        - Si ya tienes en el contexto de la conversación la información de la PQR, úsala directamente para redactar el borrador sin volver a buscar.
        - Si el usuario pide responder una PQR específica sin contexto previo:
          1. Busca con `knowledge_base_search` usando los filtros disponibles (documentId, rootDocumentId, radicated, consecutive).
          2. Si los resultados traen varias PQRs distintas, lista las encontradas con su radicado/consecutivo y pregunta cuál responder.
          3. Con el `Documento Raíz ID` identificado, haz una segunda búsqueda con `rootDocumentId` para obtener el contexto completo del caso.
        - Redacta el borrador: sin saludo inicial, sin despedida, solo el contenido sustantivo de la respuesta.
        - Solicita confirmación explícita: "¿Confirmas que deseas registrar esta respuesta en el sistema?"
        - Si el administrador pide cambios: ajusta, muestra la versión actualizada y vuelve a pedir confirmación.
        - Solo tras confirmación: invoca `create_response_pqr` directamente, sin volver a mostrar el borrador.

        ### Parámetros
        - `documentId`: valor numérico de `Documento Raíz ID` que aparece en los resultados de `knowledge_base_search`. NUNCA uses el número con el que el usuario identificó la PQR (consecutivo, radicado, etc.) — ese es solo un criterio de búsqueda, no el ID interno del sistema.
        - `contentAnswers`: texto completo aprobado por el administrador.
        - `subject`: asunto de la respuesta.

        ### Manejo del resultado
        Cuando la herramienta retorne un texto que contenga `[open_document:N]`, inclúyelo exactamente como aparece, sin modificaciones.
        DESC,
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
        private PqrIaGuard $guard,
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
        if (!$this->guard->isPqrEnabled()) {
            return 'El módulo PQR no está registrado como proceso de IA.';
        }

        try {
            $format = $this->getFormatAnswer();
            $ftPqr = UtilitiesPqr::getInstanceForDocumentId($documentId);

            $userId = $this->security->getUser()->getId();

            $vfuncionarioDc = VfuncionarioDc::getActiveRoles(userId: $userId)[0];

            $data = array_merge($this->getDefaultData(), [
                'asunto'            => $subject,
                'contenido'         => $this->toHtml($contentAnswers),
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

    private function toHtml(string $text): string
    {
        $text = trim($text);
        $paragraphs = preg_split('/\n{2,}/', $text);
        $html = '';
        foreach ($paragraphs as $paragraph) {
            $paragraph = nl2br(htmlspecialchars($paragraph, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
            $html .= "<p>$paragraph</p>";
        }

        return $html;
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
