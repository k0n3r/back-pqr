<?php

namespace App\Bundles\pqr\Controller;

use App\Bundles\pqr\Entity\PqrHtmlField;
use App\Bundles\pqr\Repository\PqrFormFieldRepository;
use App\Bundles\pqr\Services\PqrService;
use App\Exception\MissingParameterException;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
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
        PqrFormFieldRepository $pqrFormFieldRepository,
        Connection $connection,
    ): JsonResponse {
        try {
            $pqrFormField = $pqrFormFieldRepository->findOneBy(['name' => $request->query->get('name')]);
            if (!$pqrFormField) {
                throw new MissingParameterException($translator->trans("falta_nombre_campo"));
            }

            $pqrHtmlField = $pqrFormField->getHtmlField();
            $setting = json_decode($pqrFormField->getSetting());
            $queryData = $request->query->all('data');

            $data = match ($pqrHtmlField?->getType()) {
                PqrHtmlField::TYPE_DEPENDENCIA => $this->queryDependencias($connection, $setting, $queryData),
                PqrHtmlField::TYPE_LOCALIDAD   => $this->queryLocalidades($connection, $setting, $queryData),
                default                        => [],
            };
        } catch (Throwable $th) {
            $data = [];
        }

        return new JsonResponse([
            'results' => $data,
        ]);
    }

    private function queryDependencias(Connection $connection, object $setting, array $data): array
    {
        $qb = $connection->createQueryBuilder()
            ->select('iddependencia as id', 'nombre as text')
            ->from('dependencia');

        if ($data['id'] ?? null) {
            return $qb->where('iddependencia = :id')
                ->setParameter('id', $data['id'])
                ->executeQuery()
                ->fetchAllAssociative();
        }

        $qb->where('estado = 1')
            ->orderBy('nombre', 'ASC')
            ->setFirstResult(0)
            ->setMaxResults(40);

        if (isset($data['term'])) {
            $qb->andWhere('nombre LIKE :nombre')
                ->setParameter('nombre', '%'.$data['term'].'%');
        }

        if (!($setting->allDependency ?? true)) {
            $ids = array_column((array)($setting->options ?? []), 'id');
            if ($ids) {
                $qb->andWhere('iddependencia IN (:ids)')
                    ->setParameter('ids', $ids, ArrayParameterType::INTEGER);
            }
        }

        return $qb->executeQuery()->fetchAllAssociative();
    }

    private function queryLocalidades(Connection $connection, object $setting, array $data): array
    {
        $qb = $connection->createQueryBuilder()
            ->select("CONCAT(a.nombre, ' - ', b.nombre, ' - ', c.nombre) AS text", 'a.idmunicipio as id')
            ->from('municipio', 'a')
            ->join('a', 'departamento', 'b', 'a.departamento_iddepartamento = b.iddepartamento')
            ->join('b', 'pais', 'c', 'b.pais_idpais = c.idpais');

        if ($data['id'] ?? null) {
            return $qb->where('a.idmunicipio = :id')
                ->setParameter('id', $data['id'])
                ->executeQuery()
                ->fetchAllAssociative();
        }

        $qb->where("CONCAT(a.nombre, ' ', b.nombre) LIKE :query")
            ->andWhere('a.estado = 1 AND b.estado = 1 AND c.estado = 1')
            ->setParameter('query', '%'.($data['term'] ?? '').'%')
            ->orderBy('a.nombre', 'ASC')
            ->setFirstResult(0)
            ->setMaxResults(40);

        if (!($setting->allCountry ?? true)) {
            $qb->andWhere('c.idpais = :pais')
                ->setParameter('pais', $setting->country->id ?? 0);
        }

        return $qb->executeQuery()->fetchAllAssociative();
    }

    #[Route('/autocomplete/find', name: 'findDataForAutocomplete', methods: ['GET'])]
    public function findDataForAutocomplete(
        Request $request,
        PqrService $pqrService,
    ): JsonResponse {
        try {
            $data = $pqrService->findDataForAutocomplete(
                $request->query->get('type'),
                $request->query->all('data'),
            );
        } catch (Throwable $th) {
            $data = [];
        }

        return new JsonResponse([
            'results' => $data,
        ]);
    }
}
