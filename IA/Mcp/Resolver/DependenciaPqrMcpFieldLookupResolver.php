<?php

namespace App\Bundles\pqr\IA\Mcp\Resolver;

use App\Bundles\ia\Mcp\McpFieldLookupResolverInterface;
use App\Bundles\pqr\Services\models\PqrFormField;

class DependenciaPqrMcpFieldLookupResolver implements McpFieldLookupResolverInterface
{
    public function supports(string $fieldName, int $idformato): bool
    {
        return $fieldName == PqrFormField::FIELD_NAME_SYS_DEPENDENCIA;
    }

    public function search(string $fieldName, int $idformato, string $query): array
    {
        $PqrFormField = PqrFormField::findByAttributes([
            'name' => PqrFormField::FIELD_NAME_SYS_DEPENDENCIA,
        ]);

        $results = $PqrFormField->getService()->getListDataForAutocomplete(['term' => $query]);

        return array_map(
            fn (array $row)
                => [
                'id'    => (int)$row['id'],
                'label' => $row['text'],
            ],
            array_slice($results, 0, 20),
        );
    }
}
