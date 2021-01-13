<?php

namespace App\Bundles\pqr\Controller;

use Saia\controllers\CryptController;
use App\Bundles\pqr\Services\PqrService;
use App\services\response\ISaiaResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Bundles\pqr\Services\models\PqrFormField;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Route("/components", name="components_")
 */
class ComponentsController extends AbstractController
{
    /**
     * @Route("/autocomplete/list", name="getListDataForAutocomplete", methods={"GET"})
     */
    public function getListDataForAutocomplete(
        Request $request
    ): JsonResponse {

        try {
            if (!$PqrFormField = PqrFormField::findByAttributes([
                'name' => $request->get('name'),
            ])) {
                throw new \Exception("Falta el nombre del campo", 1);
            }
            $data = $PqrFormField->getService()->getListDataForAutocomplete($request->get('data'));
        } catch (\Throwable $th) {
            $data = [];
        }

        return new JsonResponse([
            'results' => $data
        ]);
    }

    /**
     * @Route("/autocomplete/find", name="findDataForAutocomplete", methods={"GET"})
     */
    public function findDataForAutocomplete(
        Request $request
    ): JsonResponse {

        try {
            $data = (new PqrService())
                ->findDataForAutocomplete($request->get('type'), $request->get('data'));
        } catch (\Throwable $th) {
            $data = [];
        }

        return new JsonResponse([
            'results' => $data
        ]);
    }
}
