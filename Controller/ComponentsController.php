<?php

namespace App\Bundles\pqr\Controller;

use App\Bundles\pqr\Services\models\PqrFormField;
use App\Bundles\pqr\Services\PqrService;
use App\Exception\MissingParameterException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

#[Route('/components', name: 'components_')]
class ComponentsController extends AbstractController
{
    #[Route('/autocomplete/list', name: 'getListDataForAutocomplete', methods: ['GET'])]
    public function getListDataForAutocomplete(
        Request $request,
        TranslatorInterface $translator,
    ): JsonResponse {
        try {
            if (!$PqrFormField = PqrFormField::findByAttributes([
                'name' => $request->get('name'),
            ])) {
                $trans = $translator->trans("falta_nombre_campo");
                throw new MissingParameterException($trans);
            }
            $data = $PqrFormField->getService()->getListDataForAutocomplete($request->get('data'));
        } catch (Throwable $th) {
            $data = [];
        }

        return new JsonResponse([
            'results' => $data,
        ]);
    }

    #[Route('/autocomplete/find', name: 'findDataForAutocomplete', methods: ['GET'])]
    public function findDataForAutocomplete(
        Request $request,
    ): JsonResponse {
        try {
            $data = (new PqrService())
                ->findDataForAutocomplete($request->get('type'), $request->get('data'));
        } catch (Throwable $th) {
            $data = [];
        }

        return new JsonResponse([
            'results' => $data,
        ]);
    }
}
