<?php

namespace App\Bundles\pqr\Controller;

use App\Bundles\pqr\formatos\pqr\FtPqr;
use App\Bundles\pqr\helpers\UtilitiesPqr;
use App\Bundles\pqr\Services\models\PqrHistory;
use App\Exception\SaiaException;
use Doctrine\DBAL\Connection;
use Saia\controllers\DateController;
use App\Bundles\pqr\Services\PqrService;
use App\services\response\ISaiaResponse;
use Saia\controllers\functions\CoreFunctions;
use Saia\models\Tercero;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Throwable;

#[Route('/{idft}', name: 'FtPqr_')]
class FtPqrController extends AbstractController
{
    /**
     * @param int           $idft
     * @param ISaiaResponse $saiaResponse
     * @return Response
     */
    #[Route('/externalUser', name: 'getExternalUser', methods: ['GET'])]
    public function getExternalUser(
        int $idft,
        ISaiaResponse $saiaResponse
    ): Response {

        try {
            $FtPqr = UtilitiesPqr::getInstanceForFtId($idft);
            $data = [
                'sys_tercero' => $FtPqr->sys_tercero,
                'fieldId'     => $this->getFieldIdSysTercero($FtPqr)
            ];

            $saiaResponse->replaceData($data);

            $saiaResponse->setSuccess(1);
        } catch (Throwable $th) {
            $saiaResponse->setMessage($th->getMessage());
        }

        return $saiaResponse->getResponse();
    }

    private function getFieldIdSysTercero(FtPqr $FtPqr): int
    {
        $callback = function () use ($FtPqr) {
            return $FtPqr->getFormat()->getField('sys_tercero')->getPK();
        };

        return CoreFunctions::createItemCache('FieldIdPqrSysTercero', $callback);
    }

    /**
     * @param int           $idft
     * @param ISaiaResponse $saiaResponse
     * @return Response
     */
    #[Route('/dataToLoadResponse', name: 'getDataToLoadResponse', methods: ['GET'])]
    public function getDataToLoadResponse(
        int $idft,
        ISaiaResponse $saiaResponse
    ): Response {

        try {
            $data = (UtilitiesPqr::getInstanceForFtId($idft))->getService()->getDataToLoadResponse();
            $saiaResponse->replaceData($data);

            $saiaResponse->setSuccess(1);
        } catch (Throwable $th) {
            $saiaResponse->setMessage($th->getMessage());
        }

        return $saiaResponse->getResponse();
    }

    /**
     * @param int           $idft
     * @param ISaiaResponse $saiaResponse
     * @param Request       $request
     * @return Response
     */
    #[Route('/dateForType', name: 'getDateForType', methods: ['GET'])]
    public function getDateForType(
        int $idft,
        ISaiaResponse $saiaResponse,
        Request $request
    ): Response {

        try {
            $FtPqr = UtilitiesPqr::getInstanceForFtId($idft);
            $FtPqr->sys_tipo = $request->get('type');
            $date = DateController::convertDate(
                $FtPqr->getService()->getDateForType(),
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
     * @param int           $idft
     * @param ISaiaResponse $saiaResponse
     * @return Response
     */
    #[Route('/valuesForType', name: 'getValuesForType', methods: ['GET'])]
    public function getValuesForType(
        int $idft,
        ISaiaResponse $saiaResponse
    ): Response {

        try {

            $FtPqr = UtilitiesPqr::getInstanceForFtId($idft);
            $DateTime = DateController::getDateTimeFromDataBase($FtPqr->sys_fecha_vencimiento);

            $options = null;
            $idDependencia = (int)$FtPqr->sys_dependencia;
            if ($idDependencia) {
                $options = [
                    'id'   => $idDependencia,
                    'text' => $FtPqr->getService()->getValueForReport('sys_dependencia')
                ];
            }

            $data = [
                'sys_tipo'              => (int)$FtPqr->sys_tipo,
                'sys_subtipo'           => (new PqrService())->subTypeExist() ? (int)$FtPqr->sys_subtipo : 0,
                'sys_fecha_vencimiento' => $DateTime->format('Y-m-d'),
                'sys_dependencia'       => $idDependencia,
                'optionsDependency'     => $options,
                'sys_frecuencia'        => (int)$FtPqr->sys_frecuencia,
                'sys_impacto'           => (int)$FtPqr->sys_impacto,
                'sys_severidad'         => (int)$FtPqr->sys_severidad
            ];

            $saiaResponse->replaceData($data);
            $saiaResponse->setSuccess(1);
        } catch (Throwable $th) {
            $saiaResponse->setMessage($th->getMessage());
        }

        return $saiaResponse->getResponse();
    }

    /**
     * @param int $idft
     * @return JsonResponse
     */
    #[Route('/history', name: 'getHistory', methods: ['GET'])]
    public function getHistory(
        int $idft
    ): JsonResponse {

        try {

            $records = (UtilitiesPqr::getInstanceForFtId($idft))->getService()->getRecordsHistory();

            $data = [
                'total' => count($records),
                'rows'  => $records
            ];
        } catch (Throwable $th) {
            $data = [
                'total' => 0,
                'rows'  => []
            ];
        }

        return new JsonResponse($data);
    }


    /**
     * @param int           $idft
     * @param Request       $request
     * @param ISaiaResponse $saiaResponse
     * @param Connection    $Connection
     * @return Response
     */
    #[Route('/externalUser', name: 'setExternalUser', methods: ['POST'])]
    public function setExternalUser(
        int $idft,
        Request $request,
        ISaiaResponse $saiaResponse,
        Connection $Connection
    ): Response {
        $Connection->beginTransaction();
        try {
            if (!$request->get('sys_tercero')) {
                throw new SaiaException("No fue posible actualizar el tercero");
            }

            $Tercero = new Tercero($request->get('sys_tercero'));
            $attributesNew = $Tercero->getAttributes(true);

            $FtPqr = new FtPqr($idft);
            $attributesOld = ($FtPqr->getTercero())->getAttributes(true);
            $FtPqr->sys_tercero = $Tercero->getPK();
            $FtPqr->save();


            $modified = [];
            $skip = [
                'imagen',
                'tipo',
                'titulo',
                'ciudad',
                'estado'
            ];

            foreach ($attributesOld as $key => $valueOld) {
                if (in_array($key, $skip)) {
                    continue;
                }

                if (isset($attributesNew[$key]) && $attributesNew[$key] !== $valueOld) {
                    $modified[] = "cambio $key: '$valueOld' por '$attributesNew[$key]'";
                }
            }

            if ($modified) {
                $PqrHistoryService = (new PqrHistory)->getService();
                $history = [
                    'fecha'          => date('Y-m-d H:i:s'),
                    'idft'           => $FtPqr->getPK(),
                    'fk_funcionario' => $PqrHistoryService->getFuncionario()->getPK(),
                    'tipo'           => PqrHistory::TIPO_MODIFICACION_TERCERO,
                    'idfk'           => $Tercero->getPK(),
                    'descripcion'    => 'Se actualizo el tercero: ' . implode(', ', $modified)
                ];
                if (!$PqrHistoryService->save($history)) {
                    throw new SaiaException(
                        $PqrHistoryService->getErrorManager()->getMessage(),
                        $PqrHistoryService->getErrorManager()->getCode()
                    );
                }
            }

            $data = [
                'correo' => (bool)$Tercero->getEmail()
            ];

            $saiaResponse->replaceData($data);
            $saiaResponse->setSuccess(1);
            $Connection->commit();
        } catch (Throwable $th) {
            $Connection->rollBack();
            $saiaResponse->setMessage($th->getMessage());
        }

        return $saiaResponse->getResponse();
    }

    /**
     * @param int           $idft
     * @param Request       $request
     * @param ISaiaResponse $saiaResponse
     * @param Connection    $Connection
     * @return Response
     */
    #[Route('/updateType', name: 'updateType', methods: ['PUT'])]
    public function updateType(
        int $idft,
        Request $request,
        ISaiaResponse $saiaResponse,
        Connection $Connection
    ): Response {
        $Connection->beginTransaction();
        try {

            $FtPqrService = (UtilitiesPqr::getInstanceForFtId($idft))->getService();
            if (!$FtPqrService->updateType($request->get('data'))) {
                throw new SaiaException(
                    $FtPqrService->getErrorManager()->getMessage(),
                    $FtPqrService->getErrorManager()->getCode(),
                );
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
     * @param int           $idft
     * @param Request       $request
     * @param ISaiaResponse $saiaResponse
     * @param Connection    $Connection
     * @return Response
     */
    #[Route('/finish', name: 'finish', methods: ['PUT'])]
    public function finish(
        int $idft,
        Request $request,
        ISaiaResponse $saiaResponse,
        Connection $Connection
    ): Response {

        try {
            $Connection->beginTransaction();

            $FtPqrService = (UtilitiesPqr::getInstanceForFtId($idft))->getService();
            if (!$FtPqrService->finish($request->get('observaciones'))) {
                throw new SaiaException(
                    $FtPqrService->getErrorManager()->getMessage(),
                    $FtPqrService->getErrorManager()->getCode(),
                );
            }

            $saiaResponse->setSuccess(1);
            $Connection->commit();
        } catch (Throwable $th) {
            $Connection->rollBack();
            $saiaResponse->setMessage($th->getMessage());
        }

        return $saiaResponse->getResponse();
    }
}
