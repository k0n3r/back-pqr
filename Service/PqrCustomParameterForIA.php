<?php

namespace App\Bundles\pqr\Service;

use App\Service\IA\CustomParameterForDocumentInterface;

class PqrCustomParameterForIA implements CustomParameterForDocumentInterface
{
    public function getSuggestions(): array
    {
        return [
            'Resume esta PQR',
            'Genere una respuesta a la PQR',
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
        return '/api/ia/chat/user';
    }
}
