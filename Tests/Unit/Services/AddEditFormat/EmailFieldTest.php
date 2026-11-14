<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Tests\Unit\Services\AddEditFormat;

use App\Bundles\pqr\Entity\PqrForm;
use App\Bundles\pqr\Entity\PqrFormField;
use App\Bundles\pqr\Services\controllers\AddEditFormat\fields\Email;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * El valor de confirmEmail viaja por un PUT form-urlencoded (jQuery $.param()),
 * que convierte cualquier booleano a texto literal ("true"/"false") — ambos
 * truthy. Este test cubre que Email::getValues() normalice correctamente
 * cualquier representación posible, incluyendo registros ya guardados antes
 * de este fix.
 */
final class EmailFieldTest extends TestCase
{
    private function fieldWithSetting(string $setting): PqrFormField
    {
        $pqrForm = (new PqrForm())
            ->setFkFormato(1)
            ->setDescriptionField(0);

        return (new PqrFormField())
            ->setName('sys_email')
            ->setLabel('E-mail')
            ->setRequired(true)
            ->setOrden(1)
            ->setPqrForm($pqrForm)
            ->setSetting($setting);
    }

    #[DataProvider('provideConfirmEmailValues')]
    public function testNormalizaConfirmarCorreoSinImportarLaRepresentacion(
        string $setting,
        bool    $esperado,
    ): void {
        $field = $this->fieldWithSetting($setting);

        $opciones = json_decode((new Email($field))->getValues()['opciones'], true);

        self::assertSame($esperado, $opciones['confirmar_correo']);
    }

    public static function provideConfirmEmailValues(): array
    {
        return [
            'sin confirmEmail en setting (default true)' => ['{}', true],
            'bool true'                        => [json_encode(['confirmEmail' => true]), true],
            'bool false'                       => [json_encode(['confirmEmail' => false]), false],
            'string "1" (checkbox nuevo)'      => [json_encode(['confirmEmail' => '1']), true],
            'string "0" (checkbox nuevo)'      => [json_encode(['confirmEmail' => '0']), false],
            'string "true" (dato ya corrupto)' => ['{"confirmEmail":"true"}', true],
            'string "false" (dato ya corrupto)' => ['{"confirmEmail":"false"}', false],
        ];
    }
}
