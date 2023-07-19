<?php

namespace App\Bundles\pqr\Controller;

use App\services\response\ISaiaResponse;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/webservice", name="webservice_")
 */
class WebserviceController extends AbstractController
{
    /**
     * @Route("/saveDocument", name="register", methods={"POST"})
     * @param Request       $Request
     * @param ISaiaResponse $saiaResponse
     * @param Connection    $Connection
     * @return Response
     */
    public function saveDocument(
        Request $Request,
        ISaiaResponse $saiaResponse,
        Connection $Connection
    ): Response {
        return (new CaptchaController())->saveDocument(
            $Request,
            $saiaResponse,
            $Connection
        );
    }
}