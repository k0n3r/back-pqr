<?php

declare(strict_types=1);

namespace App\Bundles\pqr\IA\Service;

use App\Bundles\ia\Repository\IAProcessRepository;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Verifica en tiempo de ejecución si el módulo PQR está activo
 * (existe al menos un proceso IA vinculado al formato 'pqr').
 */
readonly class PqrIaGuard
{
    public const string FORMAT_NAME = 'pqr';

    private const string CACHE_KEY_PROCESS = 'pqr_ia.process_id';

    public function __construct(
        private IAProcessRepository $iaProcessRepository,
        #[Autowire(service: 'cache.app')]
        private CacheItemPoolInterface $cache,
    ) {
    }

    public function isPqrEnabled(): bool
    {
        return $this->getPqrProcessId() !== null;
    }

    public function getPqrProcessId(): ?int
    {
        $item = $this->cache->getItem(self::CACHE_KEY_PROCESS);

        if ($item->isHit()) {
            return $item->get();
        }

        $processId = $this->iaProcessRepository->findIdByMainFormatName(self::FORMAT_NAME);
        $this->cache->save($item->set($processId));

        return $processId;
    }
}
