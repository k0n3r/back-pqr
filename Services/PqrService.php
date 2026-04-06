<?php

namespace App\Bundles\pqr\Services;

use App\Bundles\pqr\Services\models\PqrForm;
use App\Bundles\pqr\Services\models\PqrFormField;
use App\Bundles\pqr\Services\models\PqrHtmlField;
use App\Service\LegacyServiceLocator;
use Doctrine\DBAL\ParameterType;
use RuntimeException;
use Saia\models\formatos\CamposFormato;
use Saia\models\grafico\PantallaGrafico;

class PqrService
{
    public const string NAME_DEPENDENCY_GRAPH = 'pqr_dependencia';

    private ?bool $subTypeExist = null;
    private ?bool $dependencyExist = null;
    private PqrForm $PqrForm;
    private LegacyServiceLocator $serviceLocator;

    public function __construct()
    {
        $this->PqrForm = PqrForm::getInstance();
        $this->serviceLocator = LegacyServiceLocator::getInstance();
    }

    public function getPqrForm(): PqrForm
    {
        return $this->PqrForm;
    }

    /**
     * Obtiene los datos
     *
     * @param string $type
     * @param array $data
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2021
     */
    public function findDataForAutocomplete(string $type, array $data): array
    {
        $list = [];
        $records = [];
        switch ($type) {
            case 'dependencia':
                $records = $this->getListDependency($data);
                break;
            case 'pais':
                $records = $this->getListPais($data);
                break;
            case 'departamento':
                $records = $this->getListDepartamento($data);
                break;
        }

        foreach ($records as $row) {
            $list[] = [
                'id'   => $row['id'],
                'text' => $row['nombre'],
            ];
        }

        return $list;
    }

    /**
     * Obtiene una lista de dependencias
     *
     * @param array $data
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    private function getListDependency(array $data): array
    {
        $serviceLocator = LegacyServiceLocator::getInstance();
        $Qb = $serviceLocator
            ->getConnection()
            ->createQueryBuilder()
            ->select('iddependencia as id,nombre')
            ->from('dependencia')
            ->where('estado=1')
            ->orderBy('nombre', 'ASC')
            ->setFirstResult(0)
            ->setMaxResults(40);

        if ($data['term']) {
            $Qb
                ->andWhere('nombre like :nombre')
                ->setParameter('nombre', '%'.$data['term'].'%');
        }

        return $Qb->executeQuery()->fetchAllAssociative();
    }

    /**
     * Obtiene una lista de paises
     *
     * @param array $data
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    private function getListPais(array $data): array
    {
        $Qb = $this->serviceLocator
            ->getConnection()
            ->createQueryBuilder()
            ->select('idpais as id,nombre')
            ->from('pais')
            ->where('estado=1')
            ->orderBy('nombre', 'ASC')
            ->setFirstResult(0)
            ->setMaxResults(40);

        if ($data['term']) {
            $Qb
                ->andWhere('nombre like :nombre')
                ->setParameter('nombre', '%'.$data['term'].'%');
        }

        return $Qb->executeQuery()->fetchAllAssociative();
    }

    /**
     * Obtiene una lista de departamentos
     *
     * @param array $data
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    private function getListDepartamento(array $data): array
    {
        $Qb = $this->serviceLocator
            ->getConnection()
            ->createQueryBuilder()
            ->select('iddepartamento as id,nombre')
            ->from('departamento')
            ->where('estado=1')
            ->orderBy('nombre', 'ASC')
            ->setFirstResult(0)
            ->setMaxResults(40);

        if ($data['idpais']) {
            $Qb
                ->andWhere('pais_idpais=:pais')
                ->setParameter('pais', $data['idpais'], ParameterType::INTEGER);
        }

        if ($data['term']) {
            $Qb
                ->andWhere('nombre like :nombre')
                ->setParameter('nombre', '%'.$data['term'].'%');
        }

        return $Qb->executeQuery()->fetchAllAssociative();
    }


    /**
     * Obtiene los valores que se cargan en el modal
     * de los tipos/subtipos/fecha vencimiento/dependencia
     *
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function getDataForEditTypes(): array
    {
        $subType = $this->getSubTypes();

        $records = (CamposFormato::findByAttributes([
            'nombre'            => PqrFormField::FIELD_NAME_SYS_TIPO,
            'formato_idformato' => $this->getPqrForm()->fk_formato,
        ]))->getCampoOpciones();

        $data = [];
        foreach ($records as $CampoOpciones) {
            if ($CampoOpciones->estado) {
                $data[] = [
                    'id'   => $CampoOpciones->getPK(),
                    'text' => $CampoOpciones->valor,
                ];
            }
        }

        return [
            'dataType'         => $data,
            'dataSubType'      => $subType ?? [],
            'activeDependency' => (int)$this->dependencyExist(),
        ];
    }

    /**
     * Obtiene la informacion del subtype
     *
     * @return null|array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    private function getSubTypes(): ?array
    {
        if (!$this->subTypeExist()) {
            return null;
        }

        $PqrFormField = $this->getPqrForm()->getRow('sys_subtipo');
        $records = $PqrFormField->getCamposFormato()->getCampoOpciones();

        $data = [];
        foreach ($records as $CampoOpciones) {
            if ($CampoOpciones->estado) {
                $data[] = [
                    'id'   => $CampoOpciones->getPK(),
                    'text' => $CampoOpciones->valor,
                ];
            }
        }

        return $data;
    }

    /**
     * Verifica si el campo subtipo fue creado
     *
     * @return bool
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function subTypeExist(): bool
    {
        if ($this->subTypeExist !== null) {
            return $this->subTypeExist;
        }

        $this->subTypeExist = (bool)$this->getPqrForm()->getRow('sys_subtipo');

        return $this->subTypeExist;
    }

    /**
     * Verifica si el campo dependencia fue creado
     *
     * @return bool
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function dependencyExist(): bool
    {
        if ($this->dependencyExist !== null) {
            return $this->dependencyExist;
        }

        $this->dependencyExist = (bool)$this->getPqrForm()->getRow(PqrFormField::FIELD_NAME_SYS_DEPENDENCIA);

        return $this->dependencyExist;
    }

    /**
     * Obtiene los campos que se podran utilizar para la
     * carga automatica del destino de la respuesta
     *
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public static function getTextFields(): array
    {
        $serviceLocator = LegacyServiceLocator::getInstance();
        $Qb = $serviceLocator
            ->getConnection()
            ->createQueryBuilder()
            ->select('ff.*')
            ->from('pqr_form_fields', 'ff')
            ->join('ff', 'pqr_html_fields', 'hf', 'ff.fk_pqr_html_field=hf.id')
            ->where("hf.type_saia='Text' and ff.active=1")
            ->orderBy('ff.orden');

        $data = [];
        if ($records = PqrFormField::findByQueryBuilder($Qb)) {
            foreach ($records as $PqrFormField) {
                $data[] = [
                    'id'   => $PqrFormField->getPK(),
                    'text' => $PqrFormField->label,
                ];
            }
        }

        return $data;
    }

    /**
     * Obtiene los componentes para creacion del formato
     *
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     */
    public static function getDataHtmlFields(): array
    {
        $data = [];

        if ($records = PqrHtmlField::findAllByAttributes([
            'active' => 1,
        ])) {
            foreach ($records as $PqrHtmlField) {
                $data[] = $PqrHtmlField->getDataAttributes();
            }
        }

        return $data;
    }

    /**
     * Activa los indicadores preestablecidos
     *
     * @return void
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public static function activeGraphics(): void
    {
        $serviceLocator = LegacyServiceLocator::getInstance();
        if (!$PantallaGrafico = PantallaGrafico::findByAttributes([
            'nombre' => PqrForm::NOMBRE_PANTALLA_GRAFICO,
        ])) {
            $trans = $serviceLocator->getTranslator()->trans("no_se_encuentra_pantalla_grafico");
            throw new RuntimeException($trans);
        }

        $Qb = $serviceLocator
            ->getConnection()
            ->createQueryBuilder()
            ->update('grafico')
            ->where('fk_pantalla_grafico=:idpantalla')
            ->setParameter('idpantalla', $PantallaGrafico->getPK(), ParameterType::INTEGER);

        $Qb2 = clone $Qb;

        $Qb->set('estado', 1)->executeStatement();

        $PqrFormField = PqrFormField::findByAttributes([
            'name' => PqrFormField::FIELD_NAME_SYS_DEPENDENCIA,
        ]);

        if (!$PqrFormField) {
            $Qb2
                ->set('estado', 0)
                ->andWhere("nombre LIKE :graficoDependencia")
                ->setParameter('graficoDependencia', self::NAME_DEPENDENCY_GRAPH)
                ->executeStatement();
        }
    }

}
