<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Tests\Integration;

use App\Bundles\pqr\Entity\PqrForm;
use App\Bundles\pqr\Repository\PqrBackupRepository;
use App\Bundles\pqr\Repository\PqrBalancerRepository;
use App\Bundles\pqr\Repository\PqrFormFieldRepository;
use App\Bundles\pqr\Repository\PqrFormRepository;
use App\Bundles\pqr\Repository\PqrHistoryRepository;
use App\Bundles\pqr\Repository\PqrNotificationRepository;
use App\Bundles\pqr\Repository\PqrNotyMessageRepository;
use App\Bundles\pqr\Repository\PqrResponseTimeRepository;
use App\Service\LegacyServiceLocator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Contratos de borde de los repositorios ORM: ejercitan el DQL/queries reales
 * contra saia_test sin depender de datos sembrados (ids inexistentes → vacío).
 * Valida que las consultas son correctas y devuelven el tipo esperado.
 */
final class PqrOrmRepositoriesTest extends KernelTestCase
{
    private const int NONEXISTENT_ID = 999999999;

    protected function setUp(): void
    {
        self::bootKernel();
        self::getContainer()->get(LegacyServiceLocator::class);
    }

    private function get(string $class): object
    {
        return self::getContainer()->get($class);
    }

    public function testFormFieldFindByNameInexistenteDevuelveNull(): void
    {
        self::assertNull($this->get(PqrFormFieldRepository::class)->findByName('__no_existe__'));
    }

    public function testFormFieldFindByPqrFormOrderedDevuelveArray(): void
    {
        self::assertIsArray($this->get(PqrFormFieldRepository::class)->findByPqrFormOrdered(self::NONEXISTENT_ID));
    }

    public function testHistoryFindByIdftInexistenteDevuelveArrayVacio(): void
    {
        self::assertSame([], $this->get(PqrHistoryRepository::class)->findByIdft(self::NONEXISTENT_ID));
    }

    public function testNotificationFindByPqrFormInexistenteDevuelveArrayVacio(): void
    {
        self::assertSame([], $this->get(PqrNotificationRepository::class)->findByPqrForm(self::NONEXISTENT_ID));
    }

    public function testNotyMessageFindByNameInexistenteDevuelveNull(): void
    {
        self::assertNull($this->get(PqrNotyMessageRepository::class)->findByName('__no_existe__'));
    }

    public function testResponseTimeFindActiveDevuelveArrayYBySysTipoInexistenteNull(): void
    {
        $repo = $this->get(PqrResponseTimeRepository::class);
        self::assertIsArray($repo->findActive());
        self::assertNull($repo->findActiveBySysTipo(self::NONEXISTENT_ID));
    }

    public function testBalancerFindActiveDevuelveArray(): void
    {
        self::assertIsArray($this->get(PqrBalancerRepository::class)->findActive());
    }

    public function testBackupLookupsInexistentes(): void
    {
        $repo = $this->get(PqrBackupRepository::class);
        self::assertNull($repo->findByDocumento(self::NONEXISTENT_ID));
        self::assertSame([], $repo->findByPqr(self::NONEXISTENT_ID));
    }

    public function testFormRepositoryFindActiveNoLanza(): void
    {
        $result = $this->get(PqrFormRepository::class)->findActive();
        self::assertTrue($result === null || $result instanceof PqrForm);
    }

    public function testReportFieldsDataDevuelveArray(): void
    {
        // DQL con JOIN a htmlField; sin filas para un form inexistente → array vacío.
        $data = $this->get(PqrFormFieldRepository::class)->getReportFieldsData(self::NONEXISTENT_ID);
        self::assertIsArray($data);
    }
}
