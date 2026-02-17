<?php

namespace App\Bundles\pqr\Service;

use App\Service\IA\JsonForIA;

class PqrRespuestaDataJsonForIA extends JsonForIA
{
    protected const array EXCLUDED_FIELDS = [
        'despedida',
        'otra_despedida',
        'colilla',
    ];

    protected function getFieldsToExclude(): array
    {
        return array_merge(
            parent::getFieldsToExclude(),
            self::EXCLUDED_FIELDS,
        );
    }
}
