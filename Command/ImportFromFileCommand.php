<?php

namespace App\Bundles\pqr\Command;

use App\Service\UserLoginService;
use Doctrine\DBAL\Connection;
use OpenSpout\Reader\CSV\Options as CsvOptions;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\ODS\Reader as OdsReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use Psr\Log\LoggerInterface;
use Saia\controllers\SaveDocument;
use Saia\models\formatos\Formato;
use Saia\models\vistas\VfuncionarioDc;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

#[AsCommand(
    name: 'app:pqr:import-from-file',
    description: 'Importa registros de PQR desde un archivo Excel (.xlsx, .ods) o CSV.',
)]
class ImportFromFileCommand extends Command
{
    /** Cada cuántos registros se fuerza un ciclo de GC y se imprime progreso */
    private const int GC_INTERVAL = 10;

    private SymfonyStyle $io;

    /** @var array<string, int> Cache ciudad nombre → idmunicipio */
    private array $municipioCache = [];

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly Connection $connection,
        private UserLoginService $userLoginService,
        private string $projectDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument(
                'file',
                InputArgument::REQUIRED,
                'Ruta del archivo a importar (.xlsx, .ods o .csv).',
            )
            ->addOption(
                'delimiter',
                'd',
                InputOption::VALUE_OPTIONAL,
                'Delimitador para archivos CSV (por defecto: ,).',
                ',',
            )
            ->addOption(
                'skip-rows',
                's',
                InputOption::VALUE_OPTIONAL,
                'Número de filas iniciales a omitir antes de la cabecera (por defecto: 0).',
                0,
            )
            ->addOption(
                'preview',
                'p',
                InputOption::VALUE_OPTIONAL,
                'Muestra los primeros N registros en pantalla sin procesar nada (default: 10).',
                false,
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        set_time_limit(0);
        ini_set('memory_limit', '-1');
        chdir($this->projectDir.'/public');
        $this->userLoginService->loginUserIfNotAuthenticated(3);

        $this->io = new SymfonyStyle($input, $output);

        $filePath = $input->getArgument('file');
        $delimiter = $input->getOption('delimiter');
        $skipRows = (int)$input->getOption('skip-rows');
        $preview = $input->getOption('preview');

        $this->io->title('Importación de registros PQR desde archivo');

        if ($preview !== false) {
            $limit = $preview === null ? 10 : (int)$preview;

            return $this->runPreview($filePath, $delimiter, $skipRows, $limit);
        }

        $this->logger->info('Iniciando importación PQR desde archivo.', ['file' => $filePath]);

        if (!file_exists($filePath) || !is_readable($filePath)) {
            $this->io->error("El archivo no existe o no es legible: $filePath");
            $this->logger->error('Archivo no encontrado o sin permisos.', ['file' => $filePath]);

            return Command::FAILURE;
        }

        $processed = 0;
        $errors = 0;
        $headers = null;
        $rowNumber = 0;

        try {
            $reader = $this->createReader($filePath, $delimiter);
            $reader->open($filePath);

            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    $rowNumber++;

                    if ($rowNumber <= $skipRows) {
                        continue;
                    }

                    $values = $row->toArray();

                    // Primera fila válida → cabecera
                    if ($headers === null) {
                        $headers = array_map('trim', $values);
                        $this->io->info('Cabecera detectada: '.implode(', ', $headers));
                        continue;
                    }

                    // Omitir filas vacías
                    if (empty(array_filter($values, fn($v) => $v !== null && $v !== ''))) {
                        continue;
                    }

                    $record = array_combine($headers, array_slice($values, 0, count($headers)));

                    // ── Procesar registro ────────────────────────────────────
                    try {
                        $this->processRow($record, $rowNumber);
                        $processed++;

                        $this->logger->info("Fila #$rowNumber procesada correctamente.");
                    } catch (Throwable $e) {
                        $errors++;
                        $this->io->writeln("  <fg=red>✗ Fila #$rowNumber:</> {$e->getMessage()}");
                        $this->logger->error("Error en fila #$rowNumber.", [
                            'row'       => $record,
                            'exception' => $e->getMessage(),
                        ]);
                    }
                    // ────────────────────────────────────────────────────────

                    // GC y progreso cada N registros
                    if (($processed + $errors) % self::GC_INTERVAL === 0) {
                        $this->io->writeln(
                            "  Progreso: $processed ok, $errors errores (fila de archivo #$rowNumber)",
                        );
                        gc_collect_cycles();
                    }
                }

                break; // Solo primera hoja
            }

            $reader->close();
        } catch (Throwable $e) {
            $this->io->error("Error al leer el archivo: {$e->getMessage()}");
            $this->logger->error('Error fatal al leer el archivo.', [
                'file'      => $filePath,
                'exception' => $e->getMessage(),
            ]);

            return Command::FAILURE;
        }

        // ── Resumen ──────────────────────────────────────────────────────────
        $this->io->section('Resumen');
        $this->io->definitionList(
            ['Total procesados' => $processed],
            ['Con error' => $errors],
        );

        $this->logger->info('Importación finalizada.', [
            'file'      => $filePath,
            'processed' => $processed,
            'errors'    => $errors,
        ]);

        if ($errors > 0) {
            $this->io->warning("Importación completada con $errors registros fallidos.");

            return Command::FAILURE;
        }

        $this->io->success('Importación completada exitosamente.');

        return Command::SUCCESS;
    }

    /**
     * Modo preview: imprime los primeros $limit registros sin procesar nada.
     */
    private function runPreview(string $filePath, string $delimiter, int $skipRows, int $limit): int
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            $this->io->error("El archivo no existe o no es legible: $filePath");

            return Command::FAILURE;
        }

        $this->io->note("Modo PREVIEW — mostrando primeros $limit registros (sin procesar).");

        $headers = null;
        $rowNumber = 0;
        $count = 0;

        try {
            $reader = $this->createReader($filePath, $delimiter);
            $reader->open($filePath);

            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    $rowNumber++;

                    if ($rowNumber <= $skipRows) {
                        continue;
                    }

                    $values = $row->toArray();

                    if ($headers === null) {
                        $headers = array_map('trim', $values);
                        continue;
                    }

                    if (empty(array_filter($values, fn($v) => $v !== null && $v !== ''))) {
                        continue;
                    }

                    $record = array_combine($headers, array_slice($values, 0, count($headers)));
                    $count++;

                    $this->io->section("Registro #$count (fila archivo #$rowNumber)");

                    foreach ($record as $campo => $valor) {
                        if ($valor === null || $valor === '') {
                            $this->io->writeln("  <fg=gray>$campo:</> <fg=yellow>(vacío)</>");
                            continue;
                        }

                        // Campos con saltos de línea: mostrar indentado
                        $texto = (string)$valor;
                        if (str_contains($texto, "\n")) {
                            $this->io->writeln("  <fg=cyan>$campo:</>");
                            foreach (explode("\n", $texto) as $linea) {
                                $this->io->writeln("    $linea");
                            }
                        } else {
                            $this->io->writeln("  <fg=cyan>$campo:</> $texto");
                        }
                    }

                    if ($count >= $limit) {
                        break;
                    }
                }

                break; // Solo primera hoja
            }

            $reader->close();
        } catch (Throwable $e) {
            $this->io->error("Error al leer el archivo: {$e->getMessage()}");

            return Command::FAILURE;
        }

        $this->io->success("Preview completado: $count registros mostrados.");

        return Command::SUCCESS;
    }

    /**
     * @param array<string, mixed> $row Fila del archivo (clave = header de columna)
     */
    private function processRow(array $row, int $rowNumber): void
    {
        $data = [
            // ── Campos del CSV ───────────────────────────────────────────
            'sys_tipo'        => $this->mapTipoPqr($row['tipo_pqr'] ?? ''),
            'fecha_del_event' => $row['fecha_evento'] ?? '',
            'nombre'          => $row['nombre_completo'] ?? '',
            'n_mero_de_ident' => (string)($row['numero_identificacion'] ?? ''),
            'sys_email'       => $this->sanitizeEmail($row['email'] ?? ''),
            'ciudad'          => $this->resolveMunicipio($row['ciudad'] ?? ''),
            'edad'            => (string)($row['edad'] ?? ''),
            'ciudad_1'        => $row['institucion_educativa'] ?? '',
            'descripcion_1'   => $row['descripcion'] ?? '',
            'nivel_de_urgenc' => $this->mapNivelUrgencia($row['nivel_urgencia'] ?? ''),
            'sys_folios'      => (string)($row['numero_folios'] ?? '1'),
            'sys_anexos'      => $row['anexo'] ?? '',
            // ── Campos fijos ─────────────────────────────────────────────
            'sys_tratamiento' => 1,
            'formatId'        => 24,
            'webservice'      => 1,
            'dependencia'     => 3,
        ];

        $VfuncionarioDc = VfuncionarioDc::findByRole($data['dependencia']);
        $Formato = new Formato($data['formatId']);
        $SaveDocument = new SaveDocument($Formato, $VfuncionarioDc);

        if (!$SaveDocument->create($data)) {
            $this->logger->error("Fila #$rowNumber: SaveDocument::create() retornó false.", ['data' => $data]);
            throw new \RuntimeException("No se pudo crear el documento (fila #$rowNumber).");
        }

        $this->logger->debug("Fila #$rowNumber guardada.", ['id' => $row['id'] ?? null]);
    }

    private function sanitizeEmail(string $email): string
    {
        $normalized = \Normalizer::normalize(trim($email), \Normalizer::FORM_D);

        return strtolower(preg_replace('/[\x{0300}-\x{036f}]/u', '', $normalized));
    }

    private function resolveMunicipio(string $nombre): string
    {
        $key = mb_strtolower(trim($nombre));

        if (!isset($this->municipioCache[$key])) {
            $id = $this->connection->fetchOne(
                "SELECT idmunicipio FROM municipio m join departamento d on m.departamento_iddepartamento=d.iddepartamento JOIN pais p ON d.pais_idpais=p.idpais WHERE p.idpais=547 AND LOWER(m.nombre) like ? LIMIT 1",
                [$key],
            );

            if (!$id) {
                $this->logger->warning("Ciudad no encontrada, se asigna valor por defecto 15358.", ['ciudad' => $nombre]);
                $id = 15358;
            }

            $this->municipioCache[$key] = (int)$id;
        }

        return (string)$this->municipioCache[$key];
    }

    private function mapTipoPqr(string $valor): string
    {
        return match (mb_strtolower(trim($valor))) {
            'petición', 'peticion' => '143',
            'queja' => '144',
            'reclamo' => '145',
            'sugerencia' => '146',
            'felicitación', 'felicitacion' => '147',
            default => throw new \InvalidArgumentException("tipo_pqr desconocido: '$valor'"),
        };
    }

    private function mapNivelUrgencia(string $valor): string
    {
        return match (mb_strtolower(trim($valor))) {
            'alta' => '154',
            'media' => '155',
            'baja' => '156',
            default => throw new \InvalidArgumentException("nivel_urgencia desconocido: '$valor'"),
        };
    }

    private function createReader(string $filePath, string $delimiter): CsvReader|XlsxReader|OdsReader
    {
        return match (strtolower(pathinfo($filePath, PATHINFO_EXTENSION))) {
            'csv' => new CsvReader(new CsvOptions(FIELD_DELIMITER: $delimiter, FIELD_ENCLOSURE: '"')),
            'ods' => new OdsReader(),
            default => new XlsxReader(),
        };
    }

}
