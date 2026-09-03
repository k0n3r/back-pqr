<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Tests\Integration;

use App\Service\LegacyServiceLocator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Protege la migración del catálogo de traducción de este bundle a formato
 * ICU (`translations/messages+intl-icu.{es,en}.yaml`, igual que el core).
 *
 * Cubre tres cosas:
 * 1. Las claves con placeholder que YA funcionaban con la sintaxis clásica
 *    (`%email%`) siguen interpolando tras el cambio a llaves ICU (`{email}`)
 *    + el ajuste de los call sites en `FtPqrRespuestaService`/`FtPqrService`.
 * 2. `no_fue_posible_cambiar_estado_tarea` usaba `{taskId}` (sintaxis ICU)
 *    en un catálogo que ANTES no era ICU — probablemente nunca interpolaba
 *    el valor real (el placeholder se habría mostrado literal). Con el
 *    cambio de formato del catálogo, ahora sí debe interpolar.
 * 3. `correo_invalido`/`email_no_valido` colisionaban con claves del MISMO
 *    nombre en el core (mismo dominio de traducción 'messages') — el core
 *    ganaba la colisión, así que el mensaje específico de PQR (con el email
 *    inválido interpolado) probablemente NUNCA se mostró a un usuario.
 *    Renombradas con prefijo `pqr_` para no volver a colisionar.
 */
final class PqrTranslationsIcuTest extends KernelTestCase
{
    public function testEmailCopiaNoValidoInterpolaEnAmbosIdiomas(): void
    {
        self::bootKernel();
        self::getContainer()->get(LegacyServiceLocator::class);
        $translator = LegacyServiceLocator::getInstance()->getTranslator();

        self::assertSame(
            'El email en copia externa (foo@bar.com) NO es válido',
            $translator->trans('email_copia_no_valido', ['email' => 'foo@bar.com'], null, 'es'),
        );
        self::assertSame(
            'The external CC email (foo@bar.com) is NOT valid',
            $translator->trans('email_copia_no_valido', ['email' => 'foo@bar.com'], null, 'en'),
        );
    }

    public function testPqrEmailNoValidoYaNoColisionaConElCore(): void
    {
        self::bootKernel();
        self::getContainer()->get(LegacyServiceLocator::class);
        $translator = LegacyServiceLocator::getInstance()->getTranslator();

        self::assertSame(
            'El email (foo@bar.com) NO es válido',
            $translator->trans('pqr_email_no_valido', ['email' => 'foo@bar.com'], null, 'es'),
        );
    }

    public function testPqrCorreoInvalidoYaNoColisionaConElCore(): void
    {
        self::bootKernel();
        self::getContainer()->get(LegacyServiceLocator::class);
        $translator = LegacyServiceLocator::getInstance()->getTranslator();

        self::assertSame(
            'Esta dirección de correo (foo@bar.com) no es válida',
            $translator->trans('pqr_correo_invalido', ['email' => 'foo@bar.com'], null, 'es'),
        );
    }

    public function testNoFuePosibleCambiarEstadoTareaAhoraSiInterpolaElTaskId(): void
    {
        self::bootKernel();
        self::getContainer()->get(LegacyServiceLocator::class);
        $translator = LegacyServiceLocator::getInstance()->getTranslator();

        $message = $translator->trans('no_fue_posible_cambiar_estado_tarea', ['taskId' => 42], null, 'es');

        self::assertStringContainsString('42', $message);
        self::assertStringNotContainsString('{taskId}', $message);
    }
}
