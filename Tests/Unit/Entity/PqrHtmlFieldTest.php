<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Tests\Unit\Entity;

use App\Bundles\pqr\Entity\PqrHtmlField;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PqrHtmlFieldTest extends TestCase
{
    public function testDefaults(): void
    {
        $field = new PqrHtmlField();
        self::assertFalse($field->isUniq());
        self::assertTrue($field->isActive());
    }

    public function testSettersEncadenan(): void
    {
        $field = (new PqrHtmlField())
            ->setLabel('Texto')
            ->setType('text')
            ->setTypeSaia('Text')
            ->setUniq(true)
            ->setActive(false);

        self::assertSame('Texto', $field->getLabel());
        self::assertSame('text', $field->getType());
        self::assertSame('Text', $field->getTypeSaia());
        self::assertTrue($field->isUniq());
        self::assertFalse($field->isActive());
    }

    #[DataProvider('tiposParaDiasOBalance')]
    public function testIsValidFieldForResponseDaysOrBalance(string $typeSaia, bool $esperado): void
    {
        $field = (new PqrHtmlField())->setTypeSaia($typeSaia);
        self::assertSame($esperado, $field->isValidFieldForResponseDaysOrBalance());
    }

    public static function tiposParaDiasOBalance(): array
    {
        return [
            'Select'   => ['Select', true],
            'Radio'    => ['Radio', true],
            'Checkbox' => ['Checkbox', false],
            'Text'     => ['Text', false],
        ];
    }

    #[DataProvider('tiposParaOpciones')]
    public function testIsValidForOptions(string $typeSaia, bool $esperado): void
    {
        $field = (new PqrHtmlField())->setTypeSaia($typeSaia);
        self::assertSame($esperado, $field->isValidForOptions());
    }

    public static function tiposParaOpciones(): array
    {
        return [
            'Select'   => ['Select', true],
            'Radio'    => ['Radio', true],
            'Checkbox' => ['Checkbox', true],
            'Text'     => ['Text', false],
            'Date'     => ['Date', false],
        ];
    }
}
