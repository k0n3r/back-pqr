<?php

namespace App\Bundles\pqr\IA\Service;

use App\Bundles\ia\Services\CustomParameterForDocumentInterface;

class PqrCustomParameterForIA implements CustomParameterForDocumentInterface
{
    public function getSuggestions(): array
    {
        return [
            'Resume esta PQR',
            'Redacta un borrador de respuesta a esta PQR',
        ];
    }

    public function otherParams(): array
    {
        return [];
    }

    public function getContext(string $defaultContext, bool $isIndexed): string
    {
        if ($isIndexed) {
            return '';
        }

        return $defaultContext;
    }

    public function getApiUrlChatUser(): string
    {
        return '/api/pqr/ia/chat';
    }
}
