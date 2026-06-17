<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Tests\Unit\Service;

use App\Bundles\pqr\Repository\PqrLookupRepository;
use App\Bundles\pqr\Service\PqrFormFieldService;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Tests de la lógica pura/determinista de PqrFormFieldService (sin tocar BD).
 * El servicio se construye con id=null → crea una entidad nueva en memoria.
 * PqrLookupRepository es final readonly: se instancia real con Connection mock.
 */
final class PqrFormFieldServiceLogicTest extends TestCase
{
    private function service(): PqrFormFieldService
    {
        return new PqrFormFieldService(
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(Connection::class),
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(TranslatorInterface::class),
            new PqrLookupRepository($this->createMock(Connection::class)),
            null,
        );
    }

    public function testProcessAttributesBeforeUpdatingCodificaSettingAJson(): void
    {
        $result = $this->service()->processAttributesBeforeUpdating([
            'setting' => ['allDependency' => true, 'options' => []],
            'label'   => 'Mi campo',
        ]);

        self::assertSame(json_encode(['allDependency' => true, 'options' => []]), $result['setting']);
        self::assertSame('Mi campo', $result['label']);
    }

    public function testProcessAttributesBeforeUpdatingSinSettingNoLoToca(): void
    {
        $result = $this->service()->processAttributesBeforeUpdating(['label' => 'X']);

        self::assertSame(['label' => 'X'], $result);
    }

    #[DataProvider('palabras')]
    public function testIsReservedWords(string $palabra, bool $esperado): void
    {
        $method = new ReflectionMethod(PqrFormFieldService::class, 'isReservedWords');

        self::assertSame($esperado, $method->invoke($this->service(), $palabra));
    }

    public static function palabras(): array
    {
        return [
            'select'      => ['select', true],
            'from'        => ['from', true],
            'where'       => ['where', true],
            'fecha'       => ['fecha', true],
            'campo_libre' => ['campo_libre', false],
            'asunto'      => ['asunto', false],
        ];
    }

    public function testClearAttributesRecortaCadenasYRespetaNumeros(): void
    {
        $method = new ReflectionMethod(PqrFormFieldService::class, 'clearAttributes');

        $result = $method->invoke($this->service(), [
            'label'  => '  Mi campo  ',
            'orden'  => 5,
            'nested' => ['name' => "  hola\n"],
            'nulo'   => null,
        ]);

        self::assertSame('Mi campo', $result['label']);
        self::assertSame(5, $result['orden']);
        self::assertSame('hola', $result['nested']['name']);
        self::assertNull($result['nulo']);
    }
}
