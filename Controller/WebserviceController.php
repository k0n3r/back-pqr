<?php

namespace App\Bundles\pqr\Controller;

use App\Bundles\pqr\Repository\PqrNotyMessageRepository;
use App\Service\JsonResponseService;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/webservice', name: 'webservice_')]
class WebserviceController extends AbstractController
{
    #[Route('/saveDocument', name: 'register', methods: ['POST'])]
    public function saveDocument(
        Request $Request,
        jsonResponseService $json,
        Connection $Connection,
        TranslatorInterface $translator,
        PqrNotyMessageRepository $pqrNotyMessageRepository,
    ): Response {
        return (new CaptchaController())->saveDocument(
            $Request,
            $json,
            $Connection,
            $translator,
            $pqrNotyMessageRepository,
        );
    }
}
