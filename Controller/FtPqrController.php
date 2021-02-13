<?php

namespace App\Bundles\pqr\Controller;

use App\Bundles\pqr\Services\models\PqrNotyMessage;
use App\Bundles\pqr\Services\PqrNotyMessageService;
use Doctrine\DBAL\ConnectionException;
use Exception;
use Saia\core\DatabaseConnection;
use Saia\controllers\DateController;
use App\Bundles\pqr\formatos\pqr\FtPqr;
use App\Bundles\pqr\Services\PqrService;
use App\services\response\ISaiaResponse;
use Saia\models\documento\Documento;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Throwable;


/**
 * @Route("/{idft}", name="FtPqr_")
 */
class FtPqrController extends AbstractController
{

    /**
     * @Route("/dataToLoadResponse", name="getDataToLoadResponse", methods={"GET"})
     * @param int           $idft
     * @param ISaiaResponse $saiaResponse
     * @return Response
     */
    public function getDataToLoadResponse(
        int $idft,
        ISaiaResponse $saiaResponse
    ): Response
    {

        try {
            $data = (new FtPqr($idft))->getService()->getDataToLoadResponse();
            $saiaResponse->replaceData($data);

            $saiaResponse->setSuccess(1);
        } catch (Throwable $th) {
            $saiaResponse->setMessage($th->getMessage());
        }

        return $saiaResponse->getResponse();
    }

    /**
     * @Route("/dateForType", name="getDateForType", methods={"GET"})
     * @param int           $idft
     * @param ISaiaResponse $saiaResponse
     * @param Request       $request
     * @return Response
     */
    public function getDateForType(
        int $idft,
        ISaiaResponse $saiaResponse,
        Request $request
    ): Response
    {

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
        } catch (Throwable $th) {
            $saiaResponse->setMessage($th->getMessage());
        }

        return $saiaResponse->getResponse();
    }

    /**
     * @Route("/valuesForType", name="getValuesForType", methods={"GET"})
     * @param int           $idft
     * @param ISaiaResponse $saiaResponse
     * @return Response
     */
    public function getValuesForType(
        int $idft,
        ISaiaResponse $saiaResponse
    ): Response
    {

        try {

            $FtPqr = new FtPqr($idft);
            $date = DateController::convertDate(
                $FtPqr->sys_fecha_vencimiento,
                'Y-m-d'
            );

            $options = null;
            $idDependencia = (int)$FtPqr->sys_dependencia;
            if ($idDependencia) {
                $options = [
                    'id' => $idDependencia,
                    'text' => $FtPqr->getValueForReport('sys_dependencia')
                ];
            }

            $data = [
                'sys_tipo' => (int)$FtPqr->sys_tipo,
                'sys_subtipo' => (new PqrService())->subTypeExist() ? (int)$FtPqr->sys_subtipo : 0,
                'sys_fecha_vencimiento' => $date,
                'sys_dependencia' => $idDependencia,
                'optionsDependency' => $options
            ];

            $saiaResponse->replaceData($data);
            $saiaResponse->setSuccess(1);
        } catch (Throwable $th) {
            $saiaResponse->setMessage($th->getMessage());
        }

        return $saiaResponse->getResponse();
    }

    /**
     * @Route("/history", name="getHistory", methods={"GET"})
     * @param int $idft
     * @return JsonResponse
     */
    public function getHistory(
        int $idft
    ): JsonResponse
    {

        try {

            $records = (new FtPqr($idft))->getService()->getRecordsHistory();

            $data = [
                'total' => count($records),
                'rows' => $records
            ];
        } catch (Throwable $th) {
            $data = [
                'total' => 0,
                'rows' => []
            ];
        }

        return new JsonResponse($data);
    }

    /**
     * @Route("/updateType", name="updateType", methods={"PUT"})
     * @param int           $idft
     * @param Request       $request
     * @param ISaiaResponse $saiaResponse
     * @return Response
     * @throws ConnectionException
     */
    public function updateType(
        int $idft,
        Request $request,
        ISaiaResponse $saiaResponse
    ): Response
    {

        try {
            $Connection = DatabaseConnection::getDefaultConnection();
            $Connection->beginTransaction();

            $FtPqrService = (new FtPqr($idft))->getService();
            if (!$FtPqrService->updateType($request->get('data'))) {
                throw new Exception($FtPqrService->getErrorMessage(), 1);
            }

            $saiaResponse->setSuccess(1);
            $Connection->commit();
        } catch (Throwable $th) {
            $Connection->rollBack();
            $saiaResponse->setMessage($th->getMessage());
        }

        return $saiaResponse->getResponse();
    }

    /**
     * @Route("/finish", name="finish", methods={"PUT"})
     * @param int           $idft
     * @param Request       $request
     * @param ISaiaResponse $saiaResponse
     * @return Response
     * @throws ConnectionException
     */
    public function finish(
        int $idft,
        Request $request,
        ISaiaResponse $saiaResponse
    ): Response
    {

        try {
            $Connection = DatabaseConnection::getDefaultConnection();
            $Connection->beginTransaction();

            $FtPqrService = (new FtPqr($idft))->getService();
            if (!$FtPqrService->finish($request->get('observaciones'))) {
                throw new Exception($FtPqrService->getErrorMessage(), 1);
            }

            $saiaResponse->setSuccess(1);
            $Connection->commit();
        } catch (Throwable $th) {
            $Connection->rollBack();
            $saiaResponse->setMessage($th->getMessage());
        }

        return $saiaResponse->getResponse();
    }

    /**
     * @Route("/getMessage", name="getMessage", methods={"GET"})
     * @param int $idft
     * @return JsonResponse
     */
    public function getMessage(
        int $idft
    ): JsonResponse
    {
        try {
           $FtPqr = new FtPqr($idft);
           $Documento = $FtPqr->Documento;

            $message = "<br/>Su solicitud ha sido generada con el número de radicado <strong>{$Documento->numero}</strong><br/>el seguimiento lo puede realizar en el apartado de consulta con el radicado asignado<br/><br/>Gracias por visitarnos!";

            if ($PqrNotyMessage = PqrNotyMessage::findByAttributes([
                'name' => 'ws_noty_radicado'
            ])) {
                $message = PqrNotyMessageService::resolveVariables($PqrNotyMessage->message_body, $FtPqr);
            }
        } catch (Throwable $th) {
            $message = 'Error al obtener la información';
        }

        return new JsonResponse([
            'message' => $message,
            'number' => $Documento->numero ?? 0
        ]);

    }
}
