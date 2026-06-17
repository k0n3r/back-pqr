<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Tests\Unit\Entity;

use App\Bundles\pqr\Entity\PqrForm;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

final class PqrFormTest extends TestCase
{
    public function testDefaults(): void
    {
        $form = new PqrForm();

        self::assertFalse($form->isShowAnonymous());
        self::assertTrue($form->isShowLabel());
        self::assertTrue($form->isShowEmpty());
        self::assertTrue($form->isActive());
        self::assertSame(0, $form->getFkFieldTime());
        self::assertFalse($form->isEnableFilterDep());
        self::assertSame(0, $form->getDescriptionField());
        self::assertFalse($form->isEnableBalancer());
        self::assertFalse($form->isEnableConDays());
        self::assertSame(0, $form->getFkFieldBalancer());
        self::assertSame([], $form->getCanalRecepcion());
        self::assertNull($form->getResponseConfiguration());
    }

    public function testSettersEncadenanYExponenValores(): void
    {
        $form = (new PqrForm())
            ->setFkFormato(5)
            ->setFkContador(7)
            ->setLabel('PQRSF')
            ->setName('pqr')
            ->setShowAnonymous(true)
            ->setShowLabel(false)
            ->setActive(false)
            ->setFkFieldTime(3)
            ->setEnableBalancer(true)
            ->setCanalRecepcion(['WEB', 'EMAIL'])
            ->setResponseConfiguration(['tercero' => []]);

        self::assertSame(5, $form->getFkFormato());
        self::assertSame(7, $form->getFkContador());
        self::assertSame('PQRSF', $form->getLabel());
        self::assertSame('pqr', $form->getName());
        self::assertTrue($form->isShowAnonymous());
        self::assertFalse($form->isShowLabel());
        self::assertFalse($form->isActive());
        self::assertSame(3, $form->getFkFieldTime());
        self::assertTrue($form->isEnableBalancer());
        self::assertSame(['WEB', 'EMAIL'], $form->getCanalRecepcion());
        self::assertSame(['tercero' => []], $form->getResponseConfiguration());
    }

    public function testIsEnabledCalendarDaysEsAliasDeEnableConDays(): void
    {
        $form = (new PqrForm())->setEnableConDays(true);
        self::assertTrue($form->isEnabledCalendarDays());
    }

    public function testToArrayConvierteBooleanosAEnteroYConservaEstructura(): void
    {
        $form = (new PqrForm())
            ->setFkFormato(10)
            ->setFkContador(2)
            ->setLabel('Etiqueta')
            ->setName('pqr')
            ->setShowAnonymous(true)
            ->setShowLabel(false)
            ->setShowEmpty(true)
            ->setActive(true)
            ->setEnableFilterDep(true)
            ->setEnableBalancer(false)
            ->setEnableConDays(true)
            ->setFkFieldTime(4)
            ->setDescriptionField(9)
            ->setFkFieldBalancer(8)
            ->setCanalRecepcion(['WEB'])
            ->setResponseConfiguration(['k' => 'v']);

        // toArray() lee la propiedad privada $id directamente; en una entidad
        // no persistida hay que inicializarla para evitar el error de typed property.
        (new ReflectionProperty(PqrForm::class, 'id'))->setValue($form, 42);

        $array = $form->toArray();

        self::assertSame(42, $array['id']);
        self::assertSame(10, $array['fk_formato']);
        self::assertSame(2, $array['fk_contador']);
        self::assertSame(1, $array['show_anonymous']);
        self::assertSame(0, $array['show_label']);
        self::assertSame(1, $array['show_empty']);
        self::assertSame(1, $array['active']);
        self::assertSame(1, $array['enable_filter_dep']);
        self::assertSame(0, $array['enable_balancer']);
        self::assertSame(1, $array['enable_con_days']);
        self::assertSame(4, $array['fk_field_time']);
        self::assertSame(9, $array['description_field']);
        self::assertSame(8, $array['fk_field_balancer']);
        self::assertSame('Etiqueta', $array['label']);
        self::assertSame('pqr', $array['name']);
        self::assertSame(['k' => 'v'], $array['response_configuration']);
        self::assertSame(['WEB'], $array['canal_recepcion']);
    }
}
