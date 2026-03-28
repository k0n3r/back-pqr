<?php

declare(strict_types=1);

namespace App\Bundles\pqr\IA\Service;

use Doctrine\DBAL\Connection;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Verifica en tiempo de ejecución si el módulo PQR está activo
 * (existe al menos un proceso IA vinculado al formato 'pqr').
 */
readonly class PqrIaGuard
{
    private const string CACHE_KEY = 'pqr_ia.is_enabled';

    public function __construct(
        private Connection $connection,
        #[Autowire(service: 'cache.app')]
        private CacheItemPoolInterface $cache,
    ) {}

    public function isPqrEnabled(): bool
    {
        $item = $this->cache->getItem(self::CACHE_KEY);

        if ($item->isHit()) {
            return $item->get();
        }

        $enabled = (bool) $this->connection->fetchOne(
            'SELECT COUNT(*)
             FROM ia_process ip
             JOIN formato f ON f.idformato = ip.main_format_id
             WHERE f.nombre = :nombre',
            ['nombre' => 'pqr'],
        );

        $this->cache->save($item->set($enabled));

        return $enabled;
    }
}
