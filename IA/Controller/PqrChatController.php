<?php

declare(strict_types=1);

namespace App\Bundles\pqr\IA\Controller;

use App\Bundles\ia\Controller\AbstractModuleChatController;
use App\Bundles\ia\Dto\AskChat;
use App\Bundles\pqr\IA\Dto\askChatForPqr;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/ia')]
class PqrChatController extends AbstractModuleChatController
{
    #[Route('/chat', name: 'pqr_ia_chat', methods: ['POST'])]
    public function chat(Request $request): JsonResponse
    {
        return $this->handleChat($request);
    }

    protected function createDto(array $data): AskChat
    {
        return askChatForPqr::fromArray($data);
    }
}
