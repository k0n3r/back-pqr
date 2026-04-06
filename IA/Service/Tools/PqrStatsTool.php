<?php

declare(strict_types=1);

namespace App\Bundles\pqr\IA\Service\Tools;

use App\Bundles\ia\Services\Tools\AdminToolProviderInterface;
use App\Bundles\pqr\IA\Service\PqrIaGuard;
use App\Bundles\pqr\Services\models\PqrFormField;
use Doctrine\DBAL\Connection;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\AI\Agent\Toolbox\Attribute\AsTool;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Throwable;

#[AsTool(
    name: 'get_pqr_available_filters',
    description: <<<'DESC'
        Retorna los campos disponibles para filtrar o agrupar PQRs, con su tipo, descripción y opciones válidas.
        Invocar cuando no se conozca el nombre exacto del campo.
        Para sys_dependencia y sys_tipo usar get_pqr_filter_options para obtener los IDs correctos.
        DESC,
    method: 'getAvailableFilters',
)]
#[AsTool(
    name: 'get_pqr_filter_options',
    description: <<<'DESC'
        Retorna los valores disponibles (etiqueta: id) para los campos sys_dependencia y sys_tipo.
        Invocar cuando el usuario mencione una dependencia o un tipo de PQR por nombre, para obtener el ID correcto antes de llamar a count_pqr.
        Parámetro column: "sys_dependencia" o "sys_tipo".
        DESC,
    method: 'getPqrFilterOptions',
)]
#[AsTool(
    name: 'count_pqr',
    description: <<<'DESC'
        Cuenta PQRs en tiempo real sobre la vista vpqr. Solo puede filtrar por los campos listados en get_pqr_available_filters.
        NO puede filtrar por: nombre o datos del tercero/remitente, contenido del texto u otros campos no listados.
        Para esos casos usar knowledge_base_search.
        
        PARÁMETRO filtersJson: JSON array de condiciones WHERE. Cada elemento tiene:
          - "column": nombre exacto de la columna (ver get_pqr_available_filters)
          - "value": valor a comparar (igualdad estricta)
        Ejemplo: [{"column":"sys_estado","value":"PENDIENTE"},{"column":"sys_dependencia","value":"5"}]
        
        PARÁMETRO groupBy: nombre de columna para agrupar el conteo (GROUP BY).
        Ejemplo: "sys_estado" para obtener el total por cada estado.
        
        Si filtersJson está vacío ([]) y groupBy es null, retorna el total general de PQRs.
        DESC,
    method: 'countPqr',
)]
readonly class PqrStatsTool implements AdminToolProviderInterface
{
    private const string VIEW = 'vpqr';
    private const int GROUP_BY_LIMIT = 50;
    private const array ALLOWED_COLUMNS = [
        'fecha'                                  => [
            'type'        => 'datetime',
            'description' => 'Fecha de radicación de la PQR',
            'options'     => null,
        ],
        'canal_recepcion'                        => [
            'type'        => 'varchar',
            'description' => 'Medio por el que se recibió la PQR',
            'options'     => ['WEB', 'EMAIL', 'FÍSICO', 'TELEFÓNICO'],
        ],
        'sys_estado'                             => [
            'type'        => 'varchar',
            'description' => 'Estado actual de la PQR',
            'options'     => ['PENDIENTE', 'PROCESO', 'TERMINADO'],
        ],
        'sys_fecha_vencimiento'                  => [
            'type'        => 'datetime',
            'description' => 'Fecha de vencimiento de la PQR',
            'options'     => null,
        ],
        'sys_fecha_terminado'                    => [
            'type'        => 'datetime',
            'description' => 'Fecha en que se dio por resuelta la PQR',
            'options'     => null,
        ],
        'sys_oportuno'                           => [
            'type'        => 'varchar',
            'description' => 'Oportunidad de la respuesta según fecha al cierre',
            'options'     => [
                'PENDIENTES SIN VENCER',
                'VENCIDAS SIN CERRAR',
                'CERRADAS A TERMINO',
                'CERRADAS FUERA DE TERMINO',
            ],
        ],
        'sys_email'                              => [
            'type'        => 'varchar',
            'description' => 'Email del solicitante/remitente de la PQR',
            'options'     => null,
        ],
        'sys_folios'                             => [
            'type'        => 'integer',
            'description' => 'Número de folios registrados por el solicitante/remitente de la PQR',
            'options'     => null,
        ],
        PqrFormField::FIELD_NAME_SYS_DEPENDENCIA => [
            'type'        => 'integer',
            'description' => 'Dependencia interna responsable de atender la PQR (NO es el tercero o remitente que la envió)',
            'options'     => 'dynamic',
        ],
        'sys_tipo'                               => [
            'type'        => 'integer',
            'description' => 'Tipo de PQR',
            'options'     => 'dynamic',
        ],
    ];

    public function __construct(
        private Connection $connection,
        #[Autowire(service: 'cache.app')]
        private CacheItemPoolInterface $cache,
        #[Autowire(service: 'monolog.logger.ia')]
        private LoggerInterface $logger,
        private PqrIaGuard $guard,
    ) {}

    /**
     * Retorna los campos disponibles para filtrar o agrupar PQRs.
     */
    public function getAvailableFilters(): string
    {
        if (!$this->guard->isPqrEnabled()) {
            return 'El módulo PQR no está registrado como proceso de IA.';
        }

        $lines = [];

        foreach (self::ALLOWED_COLUMNS as $column => $meta) {
            $options = match (true) {
                $meta['options'] === 'dynamic' => 'usar get_pqr_filter_options para ver opciones',
                is_array($meta['options']) => implode(', ', $meta['options']),
                default => 'libre',
            };

            $lines[] = sprintf(
                '- %s (%s): %s [opciones: %s]',
                $column,
                $meta['type'],
                $meta['description'],
                $options,
            );
        }

        return "Campos disponibles para filtrar PQRs:\n".implode("\n", $lines);
    }

    /**
     * Retorna los valores disponibles (etiqueta: id) para sys_dependencia o sys_tipo.
     *
     * @param string $column "sys_dependencia" o "sys_tipo"
     */
    public function getPqrFilterOptions(string $column): string
    {
        if (!$this->guard->isPqrEnabled()) {
            return 'El módulo PQR no está registrado como proceso de IA.';
        }

        if (!in_array($column, [PqrFormField::FIELD_NAME_SYS_DEPENDENCIA, PqrFormField::FIELD_NAME_SYS_TIPO], true)) {
            return "El campo '$column' no tiene opciones dinámicas. Solo sys_dependencia y sys_tipo admiten esta consulta.";
        }

        try {
            $item = $this->cache->getItem("pqr_stats.filter_options.$column");

            if ($item->isHit()) {
                return $item->get();
            }

            $result = $column === PqrFormField::FIELD_NAME_SYS_DEPENDENCIA
                ? $this->queryDependenciaOptions()
                : $this->queryTipoOptions();

            $this->cache->save($item->set($result));

            return $result;
        } catch (Throwable $e) {
            $this->logger->error(
                'PqrStatsTool::getPqrFilterOptions error',
                ['column' => $column, 'error' => $e->getMessage()],
            );

            return "Error al obtener las opciones para '$column'.";
        }
    }

    /**
     * Cuenta PQRs con filtros y/o agrupación opcionales.
     *
     * @param string $filtersJson JSON array de {column, value}
     * @param string|null $groupBy Columna para agrupar el conteo
     */
    public function countPqr(string $filtersJson = '[]', ?string $groupBy = null): string
    {
        if (!$this->guard->isPqrEnabled()) {
            return 'El módulo PQR no está registrado como proceso de IA.';
        }

        try {
            $filters = json_decode($filtersJson, true) ?? [];
            $validColumns = array_keys(self::ALLOWED_COLUMNS);

            [$whereSql, $params, $error] = $this->buildWhere($filters, $validColumns);
            if ($error !== null) {
                return $error;
            }

            if ($groupBy !== null) {
                if (!in_array($groupBy, $validColumns, true)) {
                    return "El campo '$groupBy' no está disponible para agrupar. Usa get_pqr_available_filters para ver los campos soportados.";
                }

                return $this->queryGrouped($groupBy, $whereSql, $params);
            }

            return $this->queryTotal($whereSql, $params, $filters);
        } catch (Throwable $e) {
            $this->logger->error('PqrStatsTool::countPqr error', ['error' => $e->getMessage()]);

            return 'Error al consultar la vista vpqr.';
        }
    }

    private function buildWhere(array $filters, array $validColumns): array
    {
        $whereParts = [];
        $params = [];

        foreach ($filters as $filter) {
            $column = $filter['column'] ?? '';
            $value = $filter['value'] ?? '';

            if (!in_array($column, $validColumns, true)) {
                return [
                    null,
                    null,
                    "El campo '$column' no está disponible para filtrar. Usa get_pqr_available_filters para ver los campos soportados.",
                ];
            }

            $whereParts[] = "$column = :$column";
            $params[$column] = $value;
        }

        $whereSql = $whereParts ? 'WHERE '.implode(' AND ', $whereParts) : '';

        return [$whereSql, $params, null];
    }

    private function queryTotal(string $whereSql, array $params, array $filters): string
    {
        $sql = sprintf('SELECT COUNT(*) FROM %s %s', self::VIEW, $whereSql);
        $total = $this->connection->fetchOne($sql, $params);

        $desc = empty($filters)
            ? 'sin filtros'
            : implode(', ', array_map(fn($f) => "{$f['column']} = '{$f['value']}'", $filters));

        $this->logger->info('PqrStatsTool::countPqr', ['sql' => $sql, 'params' => $params]);

        return "Total de PQRs ($desc): $total";
    }

    private function queryGrouped(string $groupBy, string $whereSql, array $params): string
    {
        $sql = sprintf(
            'SELECT %s, COUNT(*) as total FROM %s %s GROUP BY %s ORDER BY total DESC LIMIT %d',
            $groupBy,
            self::VIEW,
            $whereSql,
            $groupBy,
            self::GROUP_BY_LIMIT + 1,
        );

        $rows = $this->connection->fetchAllAssociative($sql, $params);

        $this->logger->info('PqrStatsTool::countPqr grouped', ['sql' => $sql, 'params' => $params]);

        if (empty($rows)) {
            return "No se encontraron PQRs agrupadas por $groupBy.";
        }

        $hasMore = count($rows) > self::GROUP_BY_LIMIT;
        if ($hasMore) {
            $rows = array_slice($rows, 0, self::GROUP_BY_LIMIT);
        }

        $lines = array_map(fn($row) => sprintf('- %s: %d', $row[$groupBy] ?? '(vacío)', $row['total']), $rows);

        $output = "PQRs agrupadas por $groupBy:\n".implode("\n", $lines);

        if ($hasMore) {
            $output .= "\n\n_Nota: resultado parcial — existen más de ".self::GROUP_BY_LIMIT." grupos para este campo. Considera aplicar filtros adicionales para acotar los resultados._";
        }

        return $output;
    }

    private function queryDependenciaOptions(): string
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT iddependencia AS id, nombre AS etiqueta FROM dependencia WHERE estado = 1 ORDER BY nombre',
        );

        if (empty($rows)) {
            return 'No se encontraron dependencias disponibles.';
        }

        $lines = array_map(fn($row) => sprintf('- %s: %d', $row['etiqueta'], $row['id']), $rows);

        return "Dependencias disponibles (etiqueta: id):\n".implode("\n", $lines);
    }

    private function queryTipoOptions(): string
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT co.idcampo_opciones AS id, co.valor AS etiqueta
             FROM campo_opciones co
             JOIN campos_formato cf ON cf.idcampos_formato = co.fk_campos_formato
             JOIN formato f ON f.idformato = cf.formato_idformato
             WHERE f.nombre = :format AND cf.nombre = :campo
             ORDER BY co.llave',
            ['format' => 'pqr', 'campo' => 'sys_tipo'],
        );

        if (empty($rows)) {
            return 'No se encontraron tipos de PQR disponibles.';
        }

        $lines = array_map(fn($row) => sprintf('- %s: %d', $row['etiqueta'], $row['id']), $rows);

        return "Tipos de PQR disponibles (etiqueta: id):\n".implode("\n", $lines);
    }

    // === AdminToolProviderInterface ===

    public function getAdminToolSection(?int $processId): string
    {
        return <<<TEXT
            ## HERRAMIENTAS DE ESTADÍSTICAS PQR
            
            ### Regla para preguntas de tipo "¿cuántas?"
            
            **Siempre invocar ambos tools en paralelo**:
            1. **`count_pqr`** — si el criterio corresponde a un campo disponible, úsalo para obtener el total exacto en tiempo real. Si el criterio no está en los campos disponibles (ej. datos del tercero/remitente, contenido del documento), omite este tool.
            2. **`knowledge_base_search`** — siempre, para traer ejemplos concretos de PQRs que cumplan el criterio y enriquecer la respuesta.
            
            Al presentar la respuesta:
            - Muestra el conteo en tiempo real (si aplica) y los ejemplos encontrados en la base de conocimiento.
            - Incluye esta nota al final **solo si ambos tools retornaron datos**: "_Nota: el conteo es en tiempo real; los ejemplos provienen de la base de conocimiento, que puede tener un desfase respecto a su última sincronización._"
            
            ### Distinción clave: dependencia vs tercero/remitente
            - `sys_dependencia` = dependencia **interna** responsable de atender la PQR.
            - El tercero o remitente que **envió** la PQR es un dato externo — no está en `count_pqr`; buscarlo solo con `knowledge_base_search`.
            
            ### Herramientas disponibles
            - **`get_pqr_available_filters`**: lista los campos filtrables con sus opciones válidas.
            - **`get_pqr_filter_options`**: obtiene los IDs correctos para `sys_dependencia` y `sys_tipo` cuando el usuario los menciona por nombre.
            - **`count_pqr`**: cuenta PQRs por los campos disponibles en tiempo real.
            TEXT;
    }
}
