<?php

namespace App\Bundles\pqr\Services;

use Doctrine\DBAL\Types\Type;
use Saia\core\DatabaseConnection;
use Saia\models\formatos\CamposFormato;
use Saia\models\grafico\PantallaGrafico;
use App\Bundles\pqr\Services\models\PqrForm;
use App\Bundles\pqr\Services\models\PqrFormField;
use App\Bundles\pqr\Services\models\PqrHtmlField;

class PqrService
{

    private $subTypeExist;
    private $dependencyExist;

    private PqrForm $PqrForm;

    public function __construct()
    {
        $this->PqrForm = PqrForm::getPqrFormActive();
    }

    /**
     * Obtiene el Listado de Opciones del campo
     *
     * @param array $request
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2021
     */
    public function getListForField(array $request): array
    {
        $data = [
            'results' => []
        ];

        if (!$request['name']) {
            return $data;
        }

        if (!$PqrFormField = PqrFormField::findByAttributes([
            'name' => $request['name'],
        ])) {
            return $data;
        }

        return [
            'results' => (new PqrFormFieldService($PqrFormField))
                ->getListField($request)
        ];
    }

    /**
     * Obtiene los valores que se cargan en el modal
     * de los tipos/subtipos/fecha vencimiento/dependencia
     *
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function getDataForEditTypes(): array
    {
        $subType = $this->getSubTypes();

        $records = (CamposFormato::findByAttributes([
            'nombre' => 'sys_tipo',
            'formato_idformato' => $this->PqrForm->fk_formato
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
            'activeDependency' => (int) $this->dependencyExist()
        ];
    }

    /**
     * Obtiene la informacion del subtype
     *
     * @return null|array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    private function getSubTypes(): ?array
    {
        if (!$this->subTypeExist()) {
            return null;
        }

        $PqrFormField = $this->PqrForm->getRow('sys_subtipo');
        $records = $PqrFormField->CamposFormato->CampoOpciones;

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
     * @date 2020
     */
    public function subTypeExist(): bool
    {
        if ($this->subTypeExist !== null) {
            return $this->subTypeExist;
        }

        $this->subTypeExist = (bool) $this->PqrForm->getRow('sys_subtipo');

        return $this->subTypeExist;
    }

    /**
     * Verifica si el campo dependencia fue creado
     *
     * @return boolean
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function dependencyExist(): bool
    {
        if ($this->dependencyExist !== null) {
            return $this->dependencyExist;
        }

        $this->dependencyExist = (bool) $this->PqrForm->getRow('sys_dependencia');

        return $this->dependencyExist;
    }

    /**
     * Obtiene los campos que se podran utilizar para la
     * carga automatica del destino de la respuesta
     *
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public static function getTextFields(): array
    {
        $Qb = DatabaseConnection::getDefaultConnection()
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
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     * 
     * @throws Exception
     */
    public static function activeGraphics(): void
    {
        if (!$PantallaGrafico = PantallaGrafico::findByAttributes([
            'nombre' => PqrForm::NOMBRE_PANTALLA_GRAFICO
        ])) {
            throw new \Exception("No se encuentra la pantalla de los grafico", 200);
        }

        DatabaseConnection::getDefaultConnection()
            ->createQueryBuilder()
            ->update('grafico')
            ->set('estado', 1)
            ->where('fk_pantalla_grafico=:idpantalla')
            ->setParameter(':idpantalla', $PantallaGrafico->getPK(), Type::getType('integer'))
            ->andWhere("nombre<>'Dependencia'")
            ->execute();
    }
}
