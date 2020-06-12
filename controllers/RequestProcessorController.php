<?php

namespace Saia\Pqr\controllers;

use Saia\models\Funcionario;
use Saia\Pqr\models\PqrForm;
use Saia\Pqr\models\PqrFormField;
use Saia\Pqr\models\PqrHtmlField;
use Saia\models\vistas\VfuncionarioDc;
use Saia\controllers\FuncionarioController;
use Saia\Pqr\controllers\services\PqrFormService;
use Saia\Pqr\controllers\services\PqrFormFieldService;

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
            'pqrHtmlFields' => $this->getDataHtmlFields()
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
        ])) {
            return $response;
        }

        return [
            'results' => (new PqrFormFieldService($PqrFormField))
                ->getListField($this->request)
        ];
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
