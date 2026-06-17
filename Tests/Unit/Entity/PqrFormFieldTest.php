<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Tests\Unit\Entity;

use App\Bundles\pqr\Entity\PqrForm;
use App\Bundles\pqr\Entity\PqrFormField;
use App\Bundles\pqr\Entity\PqrHtmlField;
use PHPUnit\Framework\TestCase;

final class PqrFormFieldTest extends TestCase
{
    public function testGetSettingDecodedDevuelveObjetoDesdeJson(): void
    {
        $field = (new PqrFormField())->setSetting('{"allDependency":true,"options":[]}');

        $decoded = $field->getSettingDecoded();

        self::assertIsObject($decoded);
        self::assertTrue($decoded->allDependency);
        self::assertSame([], $decoded->options);
    }

    public function testIsActiveAceptaTrueYUno(): void
    {
        self::assertTrue((new PqrFormField())->setActive(true)->isActive());
        self::assertFalse((new PqrFormField())->setActive(false)->isActive());
    }

    public function testGetFkPqrHtmlFieldYFormDeleganEnLasRelaciones(): void
    {
        $htmlField = $this->createMock(PqrHtmlField::class);
        $htmlField->method('getId')->willReturn(3);

        $pqrForm = $this->createMock(PqrForm::class);
        $pqrForm->method('getId')->willReturn(7);

        $field = (new PqrFormField())
            ->setHtmlField($htmlField)
            ->setPqrForm($pqrForm);

        self::assertSame(3, $field->getFkPqrHtmlField());
        self::assertSame(7, $field->getFkPqrForm());
    }

    public function testGetDataAttributesExpandeLaRelacionHtmlField(): void
    {
        $htmlField = $this->createMock(PqrHtmlField::class);
        $htmlField->method('getId')->willReturn(3);
        $htmlField->method('isUniq')->willReturn(true);
        $htmlField->method('isActive')->willReturn(true);
        $htmlField->method('getLabel')->willReturn('Selección');
        $htmlField->method('getType')->willReturn('select');
        $htmlField->method('getTypeSaia')->willReturn('Select');

        $pqrForm = $this->createMock(PqrForm::class);
        $pqrForm->method('getId')->willReturn(7);

        $field = (new PqrFormField())
            ->setName('mi_campo')
            ->setLabel('Mi campo')
            ->setRequired(true)
            ->setAnonymous(false)
            ->setShowReport(true)
            ->setSetting('{}')
            ->setHtmlField($htmlField)
            ->setPqrForm($pqrForm)
            ->setFkCamposFormato(15)
            ->setIsSystem(false)
            ->setOrden(2)
            ->setActive(true);

        $data = $field->getDataAttributes();

        self::assertSame(0, $data['id']);
        self::assertSame('mi_campo', $data['name']);
        self::assertSame(1, $data['required']);
        self::assertSame(0, $data['anonymous']);
        self::assertSame(1, $data['show_report']);
        self::assertSame(7, $data['fk_pqr_form']);
        self::assertSame(15, $data['fk_campos_formato']);
        self::assertSame(2, $data['orden']);
        self::assertSame(1, $data['active']);

        self::assertSame([
            'id'        => 3,
            'uniq'      => 1,
            'active'    => 1,
            'label'     => 'Selección',
            'type'      => 'select',
            'type_saia' => 'Select',
        ], $data['fk_pqr_html_field']);
    }
}
