<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Tests\Integration;

use App\Bundles\pqr\Entity\PqrFormField;
use App\Bundles\pqr\Repository\PqrLookupRepository;
use App\Bundles\pqr\Service\PqrFormFieldService;
use App\Bundles\pqr\Service\PqrFormFieldServiceFactory;
use App\Bundles\pqr\Service\PqrHistoryService;
use App\Bundles\pqr\Service\PqrService;
use App\Service\LegacyServiceLocator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Verifica el wiring de los servicios PQR tras desacoplarlos del Service legacy
 * (Fase 1). Si faltara una dependencia inyectada (EventDispatcher, Translator,
 * PqrLookupRepository, projectDir...) el contenedor fallaría al resolverlos.
 *
 * No depende de datos: ningún constructor ejercitado aquí consulta la BD
 * (la factory crea una entidad nueva en memoria; los demás solo guardan deps).
 */
final class PqrWiringTest extends KernelTestCase
{
    protected function setUp(): void
    {
        self::bootKernel();
        // Inicializa el singleton legacy usado por código Saia\ en ejecución.
        self::getContainer()->get(LegacyServiceLocator::class);
    }

    public function testPqrServiceSeResuelveDesdeElContenedor(): void
    {
        self::assertInstanceOf(
            PqrService::class,
            self::getContainer()->get(PqrService::class),
        );
    }

    public function testPqrHistoryServiceSeResuelveDesdeElContenedor(): void
    {
        self::assertInstanceOf(
            PqrHistoryService::class,
            self::getContainer()->get(PqrHistoryService::class),
        );
    }

    public function testPqrLookupRepositorySeResuelveDesdeElContenedor(): void
    {
        self::assertInstanceOf(
            PqrLookupRepository::class,
            self::getContainer()->get(PqrLookupRepository::class),
        );
    }

    public function testLaFactoryConstruyePqrFormFieldServiceConSusDependencias(): void
    {
        /** @var PqrFormFieldServiceFactory $factory */
        $factory = self::getContainer()->get(PqrFormFieldServiceFactory::class);

        $service = $factory->create();

        self::assertInstanceOf(PqrFormFieldService::class, $service);
        self::assertInstanceOf(PqrFormField::class, $service->getModel());
    }
}
