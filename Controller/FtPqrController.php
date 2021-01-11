<?php

namespace App\Bundles\pqr\Controller;

use Saia\core\DatabaseConnection;
use Saia\controllers\DateController;
use App\Bundles\pqr\formatos\pqr\FtPqr;
use App\Bundles\pqr\Services\PqrService;
use App\services\response\ISaiaResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

class FtPqrController extends AbstractController
{

    /**
     * @Route("{idft}/dateForType", name="getDateForType", methods={"GET"})
     */
    public function getDateForType(
        int $idft,
        ISaiaResponse $saiaResponse,
        Request $request
    ): Response {

        try {
            $FtPqr = new FtPqr($idft);
            $FtPqr->sys_tipo = $request->get('type');
            $date = DateController::convertDate(
                $FtPqr->getDateForType(),
                'Y-m-d',
                'Y-m-d H:i:s'
            );

            $saiaResponse->replaceData([
                'date' => $date
            ]);
            $saiaResponse->setSuccess(1);
        } catch (\Throwable $th) {
            $saiaResponse->setMessage($th->getMessage());
        }

        return $saiaResponse->getResponse();
    }

    /**
     * @Route("{idft}/valuesForType", name="getValuesForType", methods={"GET"})
     */
    public function getValuesForType(
        int $idft,
        ISaiaResponse $saiaResponse
    ): Response {

        try {

            $FtPqr = new FtPqr($idft);
            $date = DateController::convertDate(
                $FtPqr->sys_fecha_vencimiento,
                'Y-m-d'
            );

            $idDependencia = (int) $FtPqr->sys_dependencia;
            if ($idDependencia) {
                $options = [
                    'id' => $idDependencia,
                    'text' => $FtPqr->getValueForReport('sys_dependencia')
                ];
            }

            $data = [
                'sys_tipo' => (int) $FtPqr->sys_tipo,
                'sys_subtipo' => (new PqrService())->subTypeExist() ? (int) $FtPqr->sys_subtipo : 0,
                'sys_fecha_vencimiento' => $date,
                'sys_dependencia' => $idDependencia,
                'optionsDependency' => $options
            ];

            $saiaResponse->replaceData($data);
            $saiaResponse->setSuccess(1);
        } catch (\Throwable $th) {
            $saiaResponse->setMessage($th->getMessage());
        }

        return $saiaResponse->getResponse();
    }

    /**
     * @Route("{idft}/history", name="getHistory", methods={"GET"})
     */
    public function getHistory(
        int $idft
    ): JsonResponse {

        try {

            $records = (new FtPqr($idft))->getService()->getRecordsHistory();

            $data = [
                'total' => count($records),
                'rows' => $records
            ];
        } catch (\Throwable $th) {
            $data =  [
                'total' => 0,
                'rows' => []
            ];
        }

        return new JsonResponse($data);
    }

    /**
     * @Route("{idft}/updateType", name="updateType", methods={"PUT"})
     */
    public function updateType(
        int $idft,
        Request $request,
        ISaiaResponse $saiaResponse
    ): Response {

        try {
            $Connection = DatabaseConnection::getDefaultConnection();
            $Connection->beginTransaction();

            $FtPqrService = (new FtPqr($idft))->getService();
            if (!$FtPqrService->updateType($request->get('data'))) {
                throw new \Exception($FtPqrService->getErrorMessage(), 1);
            }

            $saiaResponse->setSuccess(1);
            $Connection->commit();
        } catch (\Throwable $th) {
            $Connection->rollBack();
            $saiaResponse->setMessage($th->getMessage());
        }

        return $saiaResponse->getResponse();
    }

    /**
     * @Route("{idft}/finish", name="finish", methods={"PUT"})
     */
    public function finish(
        int $idft,
        Request $request,
        ISaiaResponse $saiaResponse
    ): Response {

        try {
            $Connection = DatabaseConnection::getDefaultConnection();
            $Connection->beginTransaction();

            $FtPqrService = (new FtPqr($idft))->getService();
            if (!$FtPqrService->finish($request->get('observaciones'))) {
                throw new \Exception($FtPqrService->getErrorMessage(), 1);
            }

            $saiaResponse->setSuccess(1);
            $Connection->commit();
        } catch (\Throwable $th) {
            $Connection->rollBack();
            $saiaResponse->setMessage($th->getMessage());
        }

        return $saiaResponse->getResponse();
    }
}
