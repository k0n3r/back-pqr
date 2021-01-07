<?php

namespace App\Bundles\pqr\Controller;

use Saia\controllers\DateController;
use Saia\controllers\CryptController;
use App\Bundles\pqr\formatos\pqr\FtPqr;
use App\Bundles\pqr\Services\PqrService;
use App\services\response\ISaiaResponse;
use App\Bundles\pqr\Services\models\PqrForm;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Bundles\pqr\Services\models\PqrFormField;
use App\Bundles\pqr\Services\PqrFormFieldService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Route("/components", name="components")
 */
class RequestProcessorController extends AbstractController
{

    /**
     * @Route("/allData", name="getAllData")
     */
    public function getAllData(
        ISaiaResponse $saiaResponse
    ): Response {

        try {
            $PqrFormService = PqrForm::getPqrFormActive()->getService();

            $data = [
                'pqrForm' => $PqrFormService->getDataPqrForm(),
                'pqrFormFields' => $PqrFormService->getDataPqrFormFields(),
                'pqrHtmlFields' => PqrService::getDataHtmlFields()
            ];

            $saiaResponse->replaceData($data);
            $saiaResponse->setSuccess(1);
        } catch (\Throwable $th) {
            $saiaResponse->setMessage($th->getMessage());
        }

        return $saiaResponse->getResponse();
    }

    /**
     * Lista la informacion de los campos Dependencia y Municipio
     *
     * @Route("/listForField", name="getListForField")
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

     * @Route("/request/processor", name="request_processor")
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
