<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Tests\Integration;

use App\Bundles\pqr\Services\FtPqrService;
use App\Service\LegacyServiceLocator;
use DateTime;
use Saia\models\documento\Documento;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Regresión: la fecha límite (y por lo tanto la tarea automática del
 * balanceador) debe vencer a las 23:59:59, no a la hora exacta de
 * radicación del documento. Antes `getDateForType()` copiaba la hora de
 * `$Created`, así que una PQR radicada de madrugada generaba una tarea
 * que expiraba minutos después de medianoche del último día hábil.
 */
final class FtPqrServiceDeadlineTest extends KernelTestCase
{
    protected function setUp(): void
    {
        self::bootKernel();
        self::getContainer()->get(LegacyServiceLocator::class);
    }

    public function testLaFechaLimiteVenceAFinDeDiaSinImportarLaHoraDeRadicacion(): void
    {
        $service = $this->buildServiceForFecha('2026-09-03 00:05:00', calendarDays: true);

        $DateTime = $service->getDateForType(true, 15);

        self::assertSame('2026-09-18', $DateTime->format('Y-m-d'));
        self::assertSame('23:59:59', $DateTime->format('H:i:s'));
    }

    /**
     * `getTaskDefaultEndDate()` ya no debe sumar 30 minutos al fin de día
     * calculado por `getDateForType()` — antes ese colchón era lo que
     * convertía 00:00:00 (hora heredada de radicación) en 00:30:00.
     */
    public function testGetTaskDefaultEndDateNoModificaLaHoraDeFinDeDia(): void
    {
        $service  = $this->buildServiceForFecha('2026-09-03 00:05:00', calendarDays: true);
        $endOfDay = new DateTime('2026-09-18 23:59:59');

        self::assertSame(
            '2026-09-18 23:59:59',
            $service->exposeGetTaskDefaultEndDate($endOfDay),
        );
    }

    private function buildServiceForFecha(string $fecha, bool $calendarDays): FtPqrService
    {
        $Documento        = new Documento();
        $Documento->fecha = $fecha;

        return new class ($Documento, $calendarDays) extends FtPqrService {
            public function __construct(
                private readonly Documento $Documento,
                private readonly bool $calendarDays,
            ) {
                parent::__construct($Documento);
            }

            public function getDocument(): Documento
            {
                return $this->Documento;
            }

            protected function isEnabledCalendarDays(): bool
            {
                return $this->calendarDays;
            }

            public function exposeGetTaskDefaultEndDate(DateTime $DateTime): string
            {
                return $this->getTaskDefaultEndDate($DateTime);
            }
        };
    }
}
