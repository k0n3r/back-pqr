<?php

declare(strict_types=1);

namespace App\Bundles\pqr\IA\Mcp\Resolver;

use App\Bundles\ia\Mcp\McpFieldLookupResolverInterface;
use App\Bundles\pqr\Entity\PqrFormField;
use Doctrine\DBAL\Connection;

class DependenciaPqrMcpFieldLookupResolver implements McpFieldLookupResolverInterface
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    public function supports(string $fieldName, int $idformato): bool
    {
        return $fieldName === PqrFormField::FIELD_NAME_SYS_DEPENDENCIA;
    }

    public function search(string $fieldName, int $idformato, string $query): array
    {
        $results = $this->connection
            ->createQueryBuilder()
            ->select('iddependencia AS id', 'nombre AS text')
            ->from('dependencia')
            ->where('estado = 1')
            ->andWhere('nombre LIKE :term')
            ->setParameter('term', "%$query%")
            ->orderBy('nombre', 'ASC')
            ->setMaxResults(20)
            ->executeQuery()
            ->fetchAllAssociative();

        return array_map(
            fn (array $row) => ['id' => (int)$row['id'], 'label' => $row['text']],
            $results,
        );
    }
}
