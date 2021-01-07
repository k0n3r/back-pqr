<?php

namespace App\Bundles\pqr\Controller;

use Saia\core\DatabaseConnection;
use App\services\response\ISaiaResponse;
use App\Bundles\pqr\Services\models\PqrForm;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class PqrFormController extends AbstractController
{

    /**
     * @Route("/form/publish", name="publish", methods={"GET"})
     */
    public function publish(
        ISaiaResponse $saiaResponse
    ): Response {

        try {
            $Connection = DatabaseConnection::getDefaultConnection();
            $Connection->beginTransaction();

            $PqrFormService = (PqrForm::getPqrFormActive())->getService();
            if (!$PqrFormService->publish()) {
                throw new \Exception($PqrFormService->getErrorMessage(), 1);
            }

            $data = [
                'pqrForm' => $PqrFormService->getDataPqrForm(),
                'pqrFormFields' => $PqrFormService->getDataPqrFormFields(),
            ];

            $saiaResponse->replaceData($data);
            $saiaResponse->setSuccess(1);
            $Connection->commit();
        } catch (\Throwable $th) {
            $Connection->rollBack();
            $saiaResponse->setMessage($th->getMessage());
        }

        return $saiaResponse->getResponse();
    }
}
