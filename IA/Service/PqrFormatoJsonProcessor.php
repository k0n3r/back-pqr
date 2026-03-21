<?php

namespace App\Bundles\pqr\IA\Service;

use App\Bundles\ia\Services\FormatoJsonProcessorInterface;
use App\Bundles\ia\Services\FormatoJsonService;

class PqrFormatoJsonProcessor implements FormatoJsonProcessorInterface
{
    public function getFormatName(): string
    {
        return 'pqr';
    }

    /**
     * @inheritDoc
     */
    public static function getPriority(): int
    {
        return 0;
    }

    public function customize(FormatoJsonService $service): void
    {
        $service->excludeFields([
            'destino_interno',
            'colilla',
            'select_mensajeria',
        ]);
    }
}
