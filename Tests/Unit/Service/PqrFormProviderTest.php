<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Tests\Unit\Service;

use App\Bundles\pqr\Entity\PqrForm;
use App\Bundles\pqr\Entity\PqrFormField;
use App\Bundles\pqr\Repository\PqrFormFieldRepository;
use App\Bundles\pqr\Repository\PqrFormRepository;
use App\Bundles\pqr\Repository\PqrNotificationRepository;
use App\Bundles\pqr\Service\PqrFormProvider;
use PHPUnit\Framework\TestCase;

final class PqrFormProviderTest extends TestCase
{
    private function provider(PqrFormRepository $formRepo, PqrFormFieldRepository $fieldRepo): PqrFormProvider
    {
        return new PqrFormProvider(
            $formRepo,
            $fieldRepo,
            $this->createMock(PqrNotificationRepository::class),
        );
    }

    private function field(string $name): PqrFormField
    {
        $field = $this->createMock(PqrFormField::class);
        $field->method('getName')->willReturn($name);

        return $field;
    }

    public function testGetFieldByNameDevuelveElCampoCoincidente(): void
    {
        $form = $this->createMock(PqrForm::class);
        $form->method('getId')->willReturn(1);

        $formRepo = $this->createMock(PqrFormRepository::class);
        $formRepo->method('findActiveOrFail')->willReturn($form);

        $dependencia = $this->field('sys_dependencia');
        $fieldRepo   = $this->createMock(PqrFormFieldRepository::class);
        $fieldRepo->method('findByPqrFormOrdered')->with(1)->willReturn([
            $this->field('sys_tipo'),
            $dependencia,
        ]);

        $provider = $this->provider($formRepo, $fieldRepo);

        self::assertSame($dependencia, $provider->getFieldByName('sys_dependencia'));
        self::assertNull($provider->getFieldByName('inexistente'));
        self::assertSame(2, $provider->countFields());
    }

    public function testGetOrNullCacheaElFormularioPorRequest(): void
    {
        $form     = $this->createMock(PqrForm::class);
        $formRepo = $this->createMock(PqrFormRepository::class);
        $formRepo->expects(self::once())->method('findActive')->willReturn($form);

        $provider = $this->provider($formRepo, $this->createMock(PqrFormFieldRepository::class));

        self::assertSame($form, $provider->getOrNull());
        self::assertSame($form, $provider->getOrNull());
    }

    public function testRefreshInvalidaElCache(): void
    {
        $formRepo = $this->createMock(PqrFormRepository::class);
        $formRepo->expects(self::exactly(2))
            ->method('findActive')
            ->willReturn($this->createMock(PqrForm::class));

        $provider = $this->provider($formRepo, $this->createMock(PqrFormFieldRepository::class));

        $provider->getOrNull();
        $provider->refresh();
        $provider->getOrNull();
    }
}
