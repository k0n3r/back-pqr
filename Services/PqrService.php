<?php

namespace App\Bundles\pqr\Services;

use App\services\GlobalContainer;
use Doctrine\DBAL\Types\Type;
use Exception;
use Saia\models\formatos\CamposFormato;
use Saia\models\grafico\PantallaGrafico;
use App\Bundles\pqr\Services\models\PqrForm;
use App\Bundles\pqr\Services\models\PqrFormField;
use App\Bundles\pqr\Services\models\PqrHtmlField;

class PqrService
{
    private ?bool $subTypeExist = null;
    private ?bool $dependencyExist = null;
    private PqrForm $PqrForm;

    public function __construct()
    {
        $this->PqrForm = PqrForm::getInstance();
    }

    public function getPqrForm(): PqrForm
    {
        return $this->PqrForm;
    }

    /**
     * Obtiene los datos
     *
     * @param string $type
     * @param array  $data
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
                'id' => $row['id'],
                'text' => $row['nombre']
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
        $Qb = GlobalContainer::getConnection()
            ->createQueryBuilder()
            ->select('iddependencia as id,nombre')
            ->from('dependencia')
            ->where('estado=1')
            ->orderBy('nombre', 'ASC')
            ->setFirstResult(0)
            ->setMaxResults(40);

        if ($data['term']) {
            $Qb->andWhere('nombre like :nombre')
                ->setParameter(':nombre', '%' . $data['term'] . '%', Type::getType('string'));
        }
        return $Qb->execute()->fetchAllAssociative();
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
        $Qb = GlobalContainer::getConnection()
            ->createQueryBuilder()
            ->select('idpais as id,nombre')
            ->from('pais')
            ->where('estado=1')
            ->orderBy('nombre', 'ASC')
            ->setFirstResult(0)
            ->setMaxResults(40);

        if ($data['term']) {
            $Qb->andWhere('nombre like :nombre')
                ->setParameter(':nombre', '%' . $data['term'] . '%', Type::getType('string'));
        }
        return $Qb->execute()->fetchAllAssociative();

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
        $Qb = GlobalContainer::getConnection()
            ->createQueryBuilder()
            ->select('iddepartamento as id,nombre')
            ->from('departamento')
            ->where('estado=1')
            ->orderBy('nombre', 'ASC')
            ->setFirstResult(0)
            ->setMaxResults(40);

        if ($data['idpais']) {
            $Qb->andWhere('pais_idpais=:pais')
                ->setParameter(':pais', $data['idpais'], Type::getType('integer'));
        }

        if ($data['term']) {
            $Qb->andWhere('nombre like :nombre')
                ->setParameter(':nombre', '%' . $data['term'] . '%', Type::getType('string'));
        }

        return $Qb->execute()->fetchAllAssociative();
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
            'nombre' => PqrFormField::FIELD_NAME_SYS_TIPO,
            'formato_idformato' => $this->getPqrForm()->fk_formato
        ]))->CampoOpciones;

        $data = [];
        foreach ($records as $CampoOpciones) {
            if ($CampoOpciones->estado) {
                $data[] = [
                    'id' => $CampoOpciones->getPK(),
                    'text' => $CampoOpciones->valor
                ];
            }
        }

        return [
            'dataType' => $data,
            'dataSubType' => $subType ?? [],
            'activeDependency' => (int)$this->dependencyExist()
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
        $records = $PqrFormField->getCamposFormato()->CampoOpciones;

        $data = [];
        foreach ($records as $CampoOpciones) {
            if ($CampoOpciones->estado) {
                $data[] = [
                    'id' => $CampoOpciones->getPK(),
                    'text' => $CampoOpciones->valor
                ];
            }
        }

        return $data;
    }

    /**
     * Verifica si el campo subtipo fue creado
     *
     * @return boolean
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
     * @return boolean
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public function dependencyExist(): bool
    {
        if ($this->dependencyExist !== null) {
            return $this->dependencyExist;
        }

        $this->dependencyExist = (bool)$this->getPqrForm()->getRow('sys_dependencia');

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
        $Qb = GlobalContainer::getConnection()
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
                    'id' => $PqrFormField->getPK(),
                    'text' => $PqrFormField->label
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
            'active' => 1
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
     * @throws Exception
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date   2020
     */
    public static function activeGraphics(): void
    {
        if (!$PantallaGrafico = PantallaGrafico::findByAttributes([
            'nombre' => PqrForm::NOMBRE_PANTALLA_GRAFICO
        ])) {
            throw new Exception("No se encuentra la pantalla de los grafico", 200);
        }

        GlobalContainer::getConnection()
            ->createQueryBuilder()
            ->update('grafico')
            ->set('estado', 1)
            ->where('fk_pantalla_grafico=:idpantalla')
            ->setParameter(':idpantalla', $PantallaGrafico->getPK(), Type::getType('integer'))
            ->andWhere("nombre<>'Dependencia'")
            ->execute();
    }

}
