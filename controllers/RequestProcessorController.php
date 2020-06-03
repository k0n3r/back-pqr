<?php

namespace Saia\Pqr\controllers;

use Saia\models\Funcionario;
use Saia\Pqr\models\PqrForm;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use Saia\core\DatabaseConnection;
use Saia\Pqr\models\PqrFormField;
use Saia\Pqr\models\PqrHtmlField;
use Saia\models\vistas\VfuncionarioDc;
use Saia\controllers\FuncionarioController;
use Saia\Pqr\controllers\services\PqrFormService;

class RequestProcessorController extends Controller
{

    /**
     * Obtiene la informacion que sera utilizada en el front
     *
     * @return void
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function getAllData(): array
    {
        $PqrForm = PqrForm::getPqrFormActive();
        $PqrFormService = new PqrFormService($PqrForm);

        $data = [
            'pqrForm' => $PqrFormService->getDataPqrForm(),
            'pqrFormFields' => $PqrFormService->getDataPqrFormFields(),
            'pqrHtmlFields' => $this->getDataHtmlFields(),
        ];

        return [
            'success' => 1,
            'data' => $data
        ];
    }

    /**
     * Obtiene los componentes para creacoin del formato
     *
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    private function getDataHtmlFields(): array
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

    public function getListForField(): array
    {
        $response = [
            'results' => []
        ];

        if (!$this->request['name']) {
            return $response;
        }

        if (!$PqrFormField = PqrFormField::findByAttributes([
            'name' => $this->request['name'],
            'active' => 1
        ])) {
            return $response;
        }

        $PqrHtmlField = $PqrFormField->PqrHtmlField;

        $ObjSettings = $PqrFormField->getSetting();
        $Qb = DatabaseConnection::getQueryBuilder();


        switch ($PqrHtmlField->type) {
            case PqrHtmlField::TYPE_DEPENDENCIA:
                $response['results'] = $this->getDependencys($ObjSettings);
                break;

            case PqrHtmlField::TYPE_LOCALIDAD:
                $response['results'] = $this->getListLocalidad($ObjSettings);
                break;
        }

        return $response;
    }

    /**
     * Obtiene listado de localidades basados en la configuracion
     * del campo
     *
     * @param object $ObjSettings
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    private function getDependencys(object $ObjSettings): array
    {
        $Qb = DatabaseConnection::getQueryBuilder();

        $Qb->select('iddependencia as id,nombre as text')
            ->from('dependencia')
            ->where('estado=1')
            ->orderBy('nombre', 'ASC')
            ->setFirstResult(0)
            ->setMaxResults(40);

        if ($this->request['term']) {
            $Qb->andWhere('nombre like :nombre')
                ->setParameter(':nombre', '%' . $this->request['term'] . '%', Type::getType('string'));
        }

        if (!$ObjSettings->allDependency) {
            $records = $ObjSettings->options;
            foreach ($records as $row) {
                $ids[] = $row->id;
            }
            $Qb->andWhere('iddependencia in (:ids)')
                ->setParameter(':ids', $ids, Connection::PARAM_INT_ARRAY);
        }

        return $Qb->execute()->fetchAll();
    }

    /**
     * Obtiene las localidades basados en la configuracion
     * del campo
     *
     * @param object $ObjSettings
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    private function getListLocalidad(object $ObjSettings): array
    {
        $Qb = DatabaseConnection::getQueryBuilder();

        $Qb->select("CONCAT(a.nombre,
            CONCAT(
                ' - ',
                CONCAT(
                    b.nombre,
                    CONCAT(
                        ' - ',
                        c.nombre   
                    )
                )
            )
        ) AS text", "a.idmunicipio as id")
            ->from('municipio', 'a')
            ->join('a', 'departamento', 'b', 'a.departamento_iddepartamento = b.iddepartamento')
            ->join('b', 'pais', 'c', 'b.pais_idpais = c.idpais')
            ->where("CONCAT(a.nombre,CONCAT(' ',b.nombre)) like :query")
            ->andWhere('a.estado = 1 AND b.estado = 1 AND c.estado = 1')
            ->setParameter('query', "%{$this->request['term']}%")
            ->orderBy('a.nombre', 'ASC')
            ->setFirstResult(0)
            ->setMaxResults(40);

        if (!$ObjSettings->allCountry) {
            $Qb->andWhere('c.idpais=:idpais')
                ->setParameter(':idpais', $ObjSettings->country->id);
        }

        return $Qb->execute()->fetchAll();
    }

    //----------------



    /**
     * Genera las credenciales de radicador web
     *
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function generateTemporalCredentials(): array
    {
        $Funcionario = new Funcionario(Funcionario::RADICADOR_WEB);
        return [
            'token' => FuncionarioController::generateToken($Funcionario, 0, true),
            'key' => $Funcionario->getPK(),
            'rol' => VfuncionarioDc::getFirstUserRole(Funcionario::RADICADOR_WEB)
        ];
    }
}
