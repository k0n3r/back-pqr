<?php

namespace App\Bundles\pqr\Controller;

use App\Bundles\pqr\Entity\PqrFormField;
use App\Bundles\pqr\Entity\PqrHistory as PqrHistoryEntity;
use App\Bundles\pqr\formatos\pqr\FtPqr;
use App\Bundles\pqr\helpers\UtilitiesPqr;
use App\Bundles\pqr\Service\PqrHistoryService;
use App\Bundles\pqr\Service\PqrService;
use App\Exception\MissingParameterException;
use App\Exception\ValidationFailedException;
use App\Service\JsonResponseService;
use Doctrine\DBAL\Connection;
use Saia\controllers\DateController;
use Saia\controllers\functions\CoreFunctions;
use Saia\models\Tercero;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

#[Route('/{idft}', name: 'FtPqr_')]
class FtPqrController extends AbstractController
{
    /**
     * @param int                 $idft
     * @param JsonResponseService $json
     *
     * @return Response
     */
    #[Route('/externalUser', name: 'getExternalUser', methods: ['GET'])]
    public function getExternalUser(
        int $idft,
        JsonResponseService $json,
    ): Response {
        try {
            $FtPqr = UtilitiesPqr::getInstanceForFtId($idft);
            $data  = [
                'sys_tercero' => $FtPqr->sys_tercero,
                'fieldId'     => $this->getFieldIdSysTercero($FtPqr),
            ];

            return $json->success($data);
        } catch (Throwable $th) {
            return $json->exception($th);
        }
    }

    private function getFieldIdSysTercero(FtPqr $FtPqr): int
    {
        $callback = function () use ($FtPqr) {
            return $FtPqr->getFormat()->getField('sys_tercero')->getPK();
        };

        return CoreFunctions::createItemCache('FieldIdPqrSysTercero', $callback);
    }

    /**
     * @param int                 $idft
     * @param JsonResponseService $json
     *
     * @return Response
     */
    #[Route('/dataToLoadResponse', name: 'getDataToLoadResponse', methods: ['GET'])]
    public function getDataToLoadResponse(
        int $idft,
        JsonResponseService $json,
    ): Response {
        try {
            $data = (UtilitiesPqr::getInstanceForFtId($idft))->getService()->getDataToLoadResponse();

            return $json->success($data);
        } catch (Throwable $th) {
            return $json->exception($th);
        }
    }

    /**
     * @param int                 $idft
     * @param JsonResponseService $json
     * @param Request             $request
     *
     * @return Response
     */
    #[Route('/dateForType', name: 'getDateForType', methods: ['GET'])]
    public function getDateForType(
        int $idft,
        JsonResponseService $json,
        Request $request,
    ): Response {
        try {
            $FtPqr           = UtilitiesPqr::getInstanceForFtId($idft);
            $FtPqr->sys_tipo = $request->query->get('type');
            $date            = DateController::convertDate(
                $FtPqr->getService()->getDateForType(),
                'Y-m-d',
                'Y-m-d H:i:s',
            );

            return $json->success(['date' => $date]);
        } catch (Throwable $th) {
            return $json->exception($th);
        }
    }

    /**
     * @param int                 $idft
     * @param JsonResponseService $json
     * @param PqrService          $pqrService
     *
     * @return Response
     */
    #[Route('/valuesForType', name: 'getValuesForType', methods: ['GET'])]
    public function getValuesForType(
        int $idft,
        JsonResponseService $json,
        PqrService $pqrService,
    ): Response {
        try {
            $FtPqr    = UtilitiesPqr::getInstanceForFtId($idft);
            $DateTime = DateController::getDateTimeFromDataBase($FtPqr->sys_fecha_vencimiento);

            $options       = null;
            $idDependencia = (int)$FtPqr->sys_dependencia;
            if ($idDependencia) {
                $options = [
                    'id'   => $idDependencia,
                    'text' => $FtPqr->getService()->getValueForReport(PqrFormField::FIELD_NAME_SYS_DEPENDENCIA),
                ];
            }

            $data = [
                'sys_tipo'                               => (int)$FtPqr->sys_tipo,
                'sys_subtipo'                            => $pqrService->subTypeExist() ? (int)$FtPqr->sys_subtipo : 0,
                'sys_fecha_vencimiento'                  => $DateTime->format('Y-m-d'),
                PqrFormField::FIELD_NAME_SYS_DEPENDENCIA => $idDependencia,
                'optionsDependency'                      => $options,
                'sys_frecuencia'                         => (int)$FtPqr->sys_frecuencia,
                'sys_impacto'                            => (int)$FtPqr->sys_impacto,
                'sys_severidad'                          => (int)$FtPqr->sys_severidad,
            ];

            return $json->success($data);
        } catch (Throwable $th) {
            return $json->exception($th);
        }
    }

    /**
     * @param int $idft
     *
     * @return JsonResponse
     */
    #[Route('/history', name: 'getHistory', methods: ['GET'])]
    public function getHistory(
        int $idft,
    ): JsonResponse {
        try {
            $records = (UtilitiesPqr::getInstanceForFtId($idft))->getService()->getRecordsHistory();

            $data = [
                'total' => count($records),
                'rows'  => $records,
            ];
        } catch (Throwable $th) {
            $data = [
                'total' => 0,
                'rows'  => [],
            ];
        }

        return new JsonResponse($data);
    }

    #[Route('/externalUser', name: 'setExternalUser', methods: ['POST'])]
    public function setExternalUser(
        int $idft,
        Request $request,
        JsonResponseService $json,
        Connection $Connection,
        TranslatorInterface $translator,
        PqrHistoryService $pqrHistoryService,
    ): Response {
        $Connection->beginTransaction();
        try {
            if (!$request->request->get('sys_tercero')) {
                $trans = $translator->trans("no_fue_posible_actualizar_tercero");
                throw new MissingParameterException($trans);
            }

            $Tercero       = new Tercero($request->request->get('sys_tercero'));
            $attributesNew = $Tercero->getAttributes(true);

            $FtPqr              = new FtPqr($idft);
            $attributesOld      = ($FtPqr->getTercero())->getAttributes(true);
            $FtPqr->sys_tercero = $Tercero->getPK();
            $FtPqr->save();


            $modified = [];
            $skip     = [
                'imagen',
                'tipo',
                'titulo',
                'ciudad',
                'estado',
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
                $pqrHistoryService->create([
                    'idft'           => $FtPqr->getPK(),
                    'fk_funcionario' => \App\Service\LegacyServiceLocator::getInstance()->getSecurity()->getUser()?->getId(),
                    'tipo'           => PqrHistoryEntity::TIPO_MODIFICACION_TERCERO,
                    'idfk'           => $Tercero->getPK(),
                    'descripcion'    => 'Se actualizo el tercero: '.implode(', ', $modified),
                ]);
            }

            $data = [
                'correo' => (bool)$Tercero->getEmail(),
            ];

            $Connection->commit();

            return $json->success($data);
        } catch (Throwable $th) {
            $Connection->rollBack();

            return $json->exception($th);
        }
    }

    /**
     * @param int                 $idft
     * @param Request             $request
     * @param JsonResponseService $json
     * @param Connection          $Connection
     *
     * @return Response
     */
    #[Route('/updateType', name: 'updateType', methods: ['PUT'])]
    public function updateType(
        int $idft,
        Request $request,
        JsonResponseService $json,
        Connection $Connection,
    ): Response {
        $Connection->beginTransaction();
        try {
            $FtPqrService = (UtilitiesPqr::getInstanceForFtId($idft))->getService();
            if (!$FtPqrService->updateType($request->request->all('data'))) {
                throw new ValidationFailedException(
                    $FtPqrService->getErrorManager()->getMessage(),
                );
            }

            $Connection->commit();

            return $json->success();
        } catch (Throwable $th) {
            $Connection->rollBack();

            return $json->exception($th);
        }
    }

    /**
     * @param int                 $idft
     * @param Request             $request
     * @param JsonResponseService $json
     * @param Connection          $Connection
     *
     * @return Response
     */
    #[Route('/finish', name: 'finish', methods: ['PUT'])]
    public function finish(
        int $idft,
        Request $request,
        JsonResponseService $json,
        Connection $Connection,
    ): Response {
        try {
            $Connection->beginTransaction();

            $FtPqrService = (UtilitiesPqr::getInstanceForFtId($idft))->getService();
            if (!$FtPqrService->finish($request->request->get('observaciones'))) {
                throw new ValidationFailedException(
                    $FtPqrService->getErrorManager()->getMessage(),
                );
            }

            $Connection->commit();

            return $json->success();
        } catch (Throwable $th) {
            $Connection->rollBack();

            return $json->exception($th);
        }
    }
}
