<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Service;

use App\Bundles\pqr\Entity\PqrForm;
use App\Bundles\pqr\Entity\PqrFormField;
use App\Bundles\pqr\Entity\PqrHtmlField;
use App\Bundles\pqr\Entity\PqrNotification;
use App\Bundles\pqr\Repository\PqrFormFieldRepository;
use App\Bundles\pqr\Repository\PqrHtmlFieldRepository;
use App\Bundles\pqr\Service\PqrFormProvider;
use Doctrine\DBAL\Connection;
use Symfony\Contracts\Translation\TranslatorInterface;
use Doctrine\DBAL\ParameterType;
use RuntimeException;
use Saia\models\formatos\CamposFormato;
use Saia\models\grafico\PantallaGrafico;

class PqrService
{
    public const string NAME_DEPENDENCY_GRAPH = 'pqr_dependencia';

    private ?bool $subTypeExist = null;
    private ?bool $dependencyExist = null;

    public function __construct(
        private readonly PqrFormProvider $pqrFormProvider,
        private readonly PqrFormFieldRepository $pqrFormFieldRepository,
        private readonly PqrHtmlFieldRepository $pqrHtmlFieldRepository,
        private readonly Connection $connection,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function getPqrForm(): PqrForm
    {
        return $this->pqrFormProvider->get();
    }

    public function findDataForAutocomplete(string $type, array $data): array
    {
        $records = match ($type) {
            'dependencia' => $this->getListDependency($data),
            'pais' => $this->getListPais($data),
            'departamento' => $this->getListDepartamento($data),
            default => [],
        };

        $list = [];
        foreach ($records as $row) {
            $list[] = ['id' => $row['id'], 'text' => $row['nombre']];
        }

        return $list;
    }

    private function getListDependency(array $data): array
    {
        $qb = $this->connection
            ->createQueryBuilder()
            ->select('iddependencia as id,nombre')
            ->from('dependencia')
            ->where('estado=1')
            ->orderBy('nombre', 'ASC')
            ->setFirstResult(0)
            ->setMaxResults(40);

        if ($data['term'] ?? null) {
            $qb->andWhere('nombre like :nombre')->setParameter('nombre', '%'.$data['term'].'%');
        }

        return $qb->executeQuery()->fetchAllAssociative();
    }

    private function getListPais(array $data): array
    {
        $qb = $this->connection
            ->createQueryBuilder()
            ->select('idpais as id,nombre')
            ->from('pais')
            ->where('estado=1')
            ->orderBy('nombre', 'ASC')
            ->setFirstResult(0)
            ->setMaxResults(40);

        if ($data['term'] ?? null) {
            $qb->andWhere('nombre like :nombre')->setParameter('nombre', '%'.$data['term'].'%');
        }

        return $qb->executeQuery()->fetchAllAssociative();
    }

    private function getListDepartamento(array $data): array
    {
        $qb = $this->connection
            ->createQueryBuilder()
            ->select('iddepartamento as id,nombre')
            ->from('departamento')
            ->where('estado=1')
            ->orderBy('nombre', 'ASC')
            ->setFirstResult(0)
            ->setMaxResults(40);

        if ($data['idpais'] ?? null) {
            $qb->andWhere('pais_idpais=:pais')->setParameter('pais', $data['idpais'], ParameterType::INTEGER);
        }
        if ($data['term'] ?? null) {
            $qb->andWhere('nombre like :nombre')->setParameter('nombre', '%'.$data['term'].'%');
        }

        return $qb->executeQuery()->fetchAllAssociative();
    }

    public function getDataForEditTypes(): array
    {
        $subType = $this->getSubTypes();

        $records = (CamposFormato::findByAttributes([
            'nombre'            => PqrFormField::FIELD_NAME_SYS_TIPO,
            'formato_idformato' => $this->getPqrForm()->getFkFormato(),
        ]))->getCampoOpciones();

        $data = [];
        foreach ($records as $CampoOpciones) {
            if ($CampoOpciones->estado) {
                $data[] = ['id' => $CampoOpciones->getPK(), 'text' => $CampoOpciones->valor];
            }
        }

        return [
            'dataType'         => $data,
            'dataSubType'      => $subType ?? [],
            'activeDependency' => (int)$this->dependencyExist(),
        ];
    }

    private function getSubTypes(): ?array
    {
        if (!$this->subTypeExist()) {
            return null;
        }

        $field = $this->pqrFormProvider->getFieldByName('sys_subtipo');
        if (!$field) {
            return null;
        }
        $CamposFormato = new CamposFormato($field->getFkCamposFormato());
        $records       = $CamposFormato->getCampoOpciones();

        $data = [];
        foreach ($records as $CampoOpciones) {
            if ($CampoOpciones->estado) {
                $data[] = ['id' => $CampoOpciones->getPK(), 'text' => $CampoOpciones->valor];
            }
        }

        return $data;
    }

    public function subTypeExist(): bool
    {
        return $this->subTypeExist ??= $this->pqrFormProvider->getFieldByName('sys_subtipo') !== null;
    }

    public function dependencyExist(): bool
    {
        return $this->dependencyExist ??= $this->pqrFormProvider->getFieldByName(
            PqrFormField::FIELD_NAME_SYS_DEPENDENCIA,
        ) !== null;
    }

    /**
     * Obtiene los campos Text disponibles para carga automática del destino de la respuesta
     */
    public function getTextFields(): array
    {
        $qb = $this->pqrFormFieldRepository
            ->createQueryBuilder('ff')
            ->innerJoin('ff.htmlField', 'hf')
            ->where("hf.typeSaia = 'Text'")
            ->andWhere('ff.active = true')
            ->orderBy('ff.orden', 'ASC');

        $data = [];
        foreach ($qb->getQuery()->getResult() as $field) {
            /** @var PqrFormField $field */
            $data[] = ['id' => $field->getId(), 'text' => $field->getLabel()];
        }

        return $data;
    }

    /**
     * Obtiene los componentes para creacion del formato
     */
    public function getDataHtmlFields(): array
    {
        $data = [];
        foreach ($this->pqrHtmlFieldRepository->findBy(['active' => true]) as $htmlField) {
            $data[] = [
                'id'        => $htmlField->getId(),
                'label'     => $htmlField->getLabel(),
                'type'      => $htmlField->getType(),
                'type_saia' => $htmlField->getTypeSaia(),
                'uniq'      => $htmlField->isUniq() ? 1 : 0,
                'active'    => $htmlField->isActive() ? 1 : 0,
            ];
        }

        return $data;
    }

    /**
     * Activa los indicadores preestablecidos
     */
    public function activeGraphics(): void
    {
        $PantallaGrafico = PantallaGrafico::findByAttributes([
            'nombre' => PqrForm::NOMBRE_PANTALLA_GRAFICO,
        ]);
        if (!$PantallaGrafico) {
            $trans = $this->translator->trans('no_se_encuentra_pantalla_grafico');
            throw new RuntimeException($trans);
        }

        $qb = $this->connection
            ->createQueryBuilder()
            ->update('grafico')
            ->where('fk_pantalla_grafico=:idpantalla')
            ->setParameter('idpantalla', $PantallaGrafico->getPK(), ParameterType::INTEGER);

        $qb2 = clone $qb;
        $qb->set('estado', '1')->executeStatement();

        $sysDep = $this->pqrFormFieldRepository->findOneBy([
            'name' => PqrFormField::FIELD_NAME_SYS_DEPENDENCIA,
        ]);
        if (!$sysDep) {
            $qb2
                ->set('estado', '0')
                ->andWhere('nombre LIKE :graficoDependencia')
                ->setParameter('graficoDependencia', self::NAME_DEPENDENCY_GRAPH)
                ->executeStatement();
        }
    }
}
