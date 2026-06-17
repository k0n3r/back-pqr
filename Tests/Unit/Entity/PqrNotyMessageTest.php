<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Tests\Unit\Entity;

use App\Bundles\pqr\Entity\PqrNotyMessage;
use PHPUnit\Framework\TestCase;

final class PqrNotyMessageTest extends TestCase
{
    public function testDefaults(): void
    {
        $msg = new PqrNotyMessage();
        self::assertSame(PqrNotyMessage::TYPE_NOTY, $msg->getType());
        self::assertTrue($msg->isActive());
        self::assertNull($msg->getDescription());
        self::assertNull($msg->getSubject());
        self::assertNull($msg->getMessageBody());
    }

    public function testSettersEncadenanYExponenValores(): void
    {
        $msg = (new PqrNotyMessage())
            ->setName('radicado')
            ->setLabel('Radicado')
            ->setDescription('desc')
            ->setSubject('asunto')
            ->setMessageBody('cuerpo')
            ->setType(PqrNotyMessage::TYPE_EMAIL)
            ->setActive(false);

        self::assertSame('radicado', $msg->getName());
        self::assertSame('Radicado', $msg->getLabel());
        self::assertSame('desc', $msg->getDescription());
        self::assertSame('asunto', $msg->getSubject());
        self::assertSame('cuerpo', $msg->getMessageBody());
        self::assertSame(PqrNotyMessage::TYPE_EMAIL, $msg->getType());
        self::assertFalse($msg->isActive());
    }
}
