<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Tests\Unit\Service;

use App\Bundles\pqr\Entity\PqrNotyMessage;
use App\Bundles\pqr\Repository\PqrNotyMessageRepository;
use App\Bundles\pqr\Service\PqrNotyMessageService;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class PqrNotyMessageServiceTest extends TestCase
{
    public function testGetDataPqrNotyMessagesMapeaLosMensajesActivos(): void
    {
        $msg = $this->createMock(PqrNotyMessage::class);
        $msg->method('getLabel')->willReturn('Radicado');
        $msg->method('getId')->willReturn(1);
        $msg->method('getDescription')->willReturn('desc');
        $msg->method('getSubject')->willReturn('asunto');
        $msg->method('getMessageBody')->willReturn('cuerpo');
        $msg->method('getType')->willReturn(PqrNotyMessage::TYPE_EMAIL);

        $repository = $this->createMock(PqrNotyMessageRepository::class);
        $repository->expects(self::once())
            ->method('findBy')
            ->with(['active' => true])
            ->willReturn([$msg]);

        $service = new PqrNotyMessageService($repository, $this->createMock(TranslatorInterface::class));

        self::assertSame([
            [
                'text'  => 'Radicado',
                'value' => [
                    'id'           => 1,
                    'description'  => 'desc',
                    'subject'      => 'asunto',
                    'message_body' => 'cuerpo',
                    'type'         => PqrNotyMessage::TYPE_EMAIL,
                ],
            ],
        ], $service->getDataPqrNotyMessages());
    }
}
