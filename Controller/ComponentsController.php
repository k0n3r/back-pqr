<?php

namespace App\Bundles\pqr\Controller;

use App\Bundles\pqr\Services\PqrService;
use App\Exception\SaiaException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Bundles\pqr\Services\models\PqrFormField;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Throwable;

#[Route('/components', name: 'components_')]
class ComponentsController extends AbstractController
{
    /**
     * @param Request $request
     * @return JsonResponse
     */
    #[Route('/autocomplete/list', name: 'getListDataForAutocomplete', methods: ['GET'])]
    public function getListDataForAutocomplete(
        Request $request
    ): JsonResponse {

        try {
            if (!$PqrFormField = PqrFormField::findByAttributes([
                'name' => $request->get('name'),
            ])) {
                throw new SaiaException("Falta el nombre del campo", 1);
            }
            $data = $PqrFormField->getService()->getListDataForAutocomplete($request->get('data'));
        } catch (Throwable $th) {
            $data = [];
        }

        return new JsonResponse([
            'results' => $data
        ]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    #[Route('/autocomplete/find', name: 'findDataForAutocomplete', methods: ['GET'])]
    public function findDataForAutocomplete(
        Request $request
    ): JsonResponse {

        try {
            $data = (new PqrService())
                ->findDataForAutocomplete($request->get('type'), $request->get('data'));
        } catch (Throwable $th) {
            $data = [];
        }

        return new JsonResponse([
            'results' => $data
        ]);
    }
}
