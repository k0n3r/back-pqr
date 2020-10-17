<?php

namespace Saia\Pqr\controllers;

use Saia\Pqr\models\PqrForm;
use Saia\Pqr\models\PqrFormField;
use Saia\Pqr\models\PqrHtmlField;
use Saia\controllers\CryptController;
use Saia\controllers\DateController;
use Saia\Pqr\controllers\services\PqrFormService;
use Saia\Pqr\controllers\services\PqrFormFieldService;
use Saia\Pqr\formatos\pqr\FtPqr;

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

    /**
     * Lista la informacion de los campos Dependencia y Municipio
     *
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
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

    /**
     * Desencripta la informacion
     *
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function decrypt(): array
    {
        $data = json_decode(CryptController::decrypt($this->request['dataCrypt']), true);

        return [
            'data' => $data
        ];
    }

    /**
     * retonar la fecha de vencimiento basado en la fecha de creacion y tipo
     *
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function getDateForType(): array
    {
        $FtPqr = new FtPqr($this->request['idft']);
        $FtPqr->sys_tipo = $this->request['type'];
        $onlyDate = DateController::convertDate($FtPqr->getDateForType(), 'Y-m-d', 'Y-m-d H:i:s');
        return [
            'success' => 1,
            'date' => $onlyDate
        ];
    }
}
