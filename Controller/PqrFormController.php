<?php

namespace App\Bundles\pqr\Controller;

use App\Bundles\pqr\Entity\PqrFormField;
use App\Bundles\pqr\Repository\PqrFormFieldRepository;
use App\Bundles\pqr\Service\PqrFormProvider;
use App\Bundles\pqr\Services\FtPqrService;
use App\Bundles\pqr\Services\PqrFormFieldService;
use App\Bundles\pqr\Services\PqrFormService;
use App\Bundles\pqr\Services\PqrService;
use Doctrine\ORM\EntityManagerInterface;
use App\Exception\MissingParameterException;
use App\Exception\ValidationFailedException;
use App\Service\JsonResponseService;
use Doctrine\DBAL\Connection;
use Saia\models\funcion\Funcion;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

#[Route('/form', name: 'form_')]
class PqrFormController extends AbstractController
{
    #[Route('/textFields', name: 'getTextFields', methods: ['GET'])]
    public function getTextFields(
        JsonResponseService $json,
        PqrService $pqrService,
    ): Response {
        try {
            return $json->success($pqrService->getTextFields());
        } catch (Throwable $th) {
            return $json->exception($th);
        }
    }

    #[Route('/setting', name: 'getSetting', methods: ['GET'])]
    public function getSetting(
        jsonResponseService $json,
        PqrFormService $pqrFormService,
    ): Response {
        try {
            $data = $pqrFormService->getSetting();

            return $json->success($data);
        } catch (Throwable $th) {
            return $json->exception($th);
        }
    }

    #[Route('/responseSetting', name: 'getResponseSetting', methods: ['GET'])]
    public function getResponseSetting(
        jsonResponseService $json,
        PqrFormService $pqrFormService,
    ): Response {
        try {
            $data = $pqrFormService->getResponseConfiguration(true) ?? [];

            return $json->success($data);
        } catch (Throwable $th) {
            return $json->exception($th);
        }
    }

    #[Route('/publish', name: 'publish', methods: ['PUT'])]
    public function publish(
        jsonResponseService $json,
        Connection $connection,
        PqrFormService $PqrFormService,
    ): Response {
        $connection->beginTransaction();

        try {
            if (!$PqrFormService->publish()) {
                throw new ValidationFailedException(
                    $PqrFormService->getErrorManager()->getMessage(),
                );
            }

            $data = [
                'pqrForm'       => $PqrFormService->getDataPqrForm(),
                'pqrFormFields' => $PqrFormService->getDataPqrFormFields(),
            ];

            $connection->commit();

            return $json->success($data);
        } catch (Throwable $th) {
            $connection->rollBack();

            return $json->exception($th);
        }
    }

    #[Route('/sortFields', name: 'sortFields', methods: ['PUT'])]
    public function sortFields(
        Request $request,
        jsonResponseService $json,
        Connection $connection,
        PqrFormFieldRepository $pqrFormFieldRepository,
        EntityManagerInterface $em,
    ): Response {
        $connection->beginTransaction();
        try {
            foreach ($request->request->all('fieldOrder') as $record) {
                $field = $pqrFormFieldRepository->find($record['id']);
                if (!$field) {
                    throw new ValidationFailedException("No fue posible actualizar el orden");
                }
                $field->setOrden($record['order'] + PqrFormFieldService::INITIAL_ORDER);
            }
            $em->flush();

            $connection->commit();

            return $json->success();
        } catch (Throwable $th) {
            $connection->rollBack();

            return $json->exception($th);
        }
    }

    #[Route('/updateSetting', name: 'updateSetting', methods: ['PUT'])]
    public function updateSetting(
        Request $request,
        jsonResponseService $json,
        Connection $connection,
        PqrFormService $PqrFormService,
    ): Response {
        $connection->beginTransaction();
        try {
            if (!$PqrFormService->updateSetting($request->request->all('data'))) {
                throw new ValidationFailedException(
                    $PqrFormService->getErrorManager()->getMessage(),
                );
            }

            $data = [
                'pqrForm'       => $PqrFormService->getDataPqrForm(),
                'pqrFormFields' => $PqrFormService->getDataPqrFormFields(),
            ];

            $connection->commit();

            return $json->success($data);
        } catch (Throwable $th) {
            $connection->rollBack();

            return $json->exception($th);
        }
    }

    #[Route('/updateResponseSetting', name: 'updateResponseSetting', methods: ['PUT'])]
    public function updateResponseSetting(
        Request $request,
        jsonResponseService $json,
        Connection $connection,
        PqrFormService $PqrFormService,
    ): Response {
        $connection->beginTransaction();
        try {
            if (!$PqrFormService->updateResponseSetting($request->request->all('data'))) {
                throw new ValidationFailedException(
                    $PqrFormService->getErrorManager()->getMessage(),
                );
            }

            $connection->commit();

            return $json->success();
        } catch (Throwable $th) {
            $connection->rollBack();

            return $json->exception($th);
        }
    }

    #[Route('/updateShowReport', name: 'updateShowReport', methods: ['PUT'])]
    public function updateShowReport(
        Request $request,
        jsonResponseService $json,
        Connection $connection,
        PqrFormService $PqrFormService,
        PqrFormFieldRepository $pqrFormFieldRepository,
        EntityManagerInterface $em,
    ): Response {
        $connection->beginTransaction();
        try {
            $connection
                ->createQueryBuilder()
                ->update('pqr_form_fields')
                ->set('show_report', 0)->executeStatement();
            $em->clear();

            foreach ($request->request->all('ids') as $id) {
                $field = $pqrFormFieldRepository->find($id);
                if ($field) {
                    $field->setShowReport(true);
                }
            }
            $em->flush();

            $PqrFormService->generaReport();
            $data = $PqrFormService->getDataPqrFormFields();

            $connection->commit();

            return $json->success($data);
        } catch (Throwable $th) {
            $connection->rollBack();

            return $json->exception($th);
        }
    }

    /**
     * Actualiza el campo mostrar/ocultar campos vacios
     *
     * @param Request             $Request
     * @param jsonResponseService $json
     * @param Connection          $connection
     * @param PqrFormService      $PqrFormService
     *
     * @return Response
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2021-10-05
     */
    #[Route('/showEmpty', name: 'updateShowEmpty', methods: ['PUT'])]
    public function updateShowEmpty(
        Request $Request,
        jsonResponseService $json,
        Connection $connection,
        PqrFormService $PqrFormService,
    ): Response {
        $connection->beginTransaction();
        try {
            $success = $PqrFormService->save([
                'show_empty' => $Request->request->get('show_empty', 1),
            ]);
            if (!$success) {
                throw new ValidationFailedException($PqrFormService->getErrorManager()->getMessage());
            }

            $data = $PqrFormService->getDataPqrForm();
            $connection->commit();

            return $json->success($data);
        } catch (Throwable $th) {
            $connection->rollBack();

            return $json->exception($th);
        }
    }

    /**
     * Habilita y aplica el filtro por dependencia a los reportes
     *
     * @param Request             $Request
     * @param jsonResponseService $json
     * @param Connection          $connection
     * @param TranslatorInterface $translator
     * @param PqrFormService      $PqrFormService
     * @param PqrFormProvider     $pqrFormProvider
     *
     * @return Response
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2022-07-01
     */
    #[Route('/filterReport', name: 'updateEnableFilterDep', methods: ['PUT'])]
    public function updateEnableFilterDep(
        Request $Request,
        jsonResponseService $json,
        Connection $connection,
        TranslatorInterface $translator,
        PqrFormService $PqrFormService,
        PqrFormProvider $pqrFormProvider,
    ): Response {
        $connection->beginTransaction();
        try {
            $status = $Request->request->get('enable_filter_dep', 0);

            if ($status && !$pqrFormProvider->getFieldByName(PqrFormField::FIELD_NAME_SYS_DEPENDENCIA)) {
                $trans = $translator->trans("agregar_componente_dependencia");
                throw new ValidationFailedException($trans);
            }

            $this->editOrCreateFunction(FtPqrService::FUNCTION_ADMIN_PQR, $status);
            $this->editOrCreateFunction(FtPqrService::FUNCTION_ADMIN_DEP_PQR, $status);


            $success = $PqrFormService->save([
                'enable_filter_dep' => $status,
            ]);

            if (!$success) {
                throw new ValidationFailedException($PqrFormService->getErrorManager()->getMessage());
            }

            $data = $PqrFormService->getDataPqrForm();
            $connection->commit();

            return $json->success($data);
        } catch (Throwable $th) {
            $connection->rollBack();

            return $json->exception($th);
        }
    }

    /**
     * Habilita/deshabilita el balanceo
     *
     * @param Request             $Request
     * @param jsonResponseService $json
     * @param Connection          $connection
     * @param PqrFormService      $PqrFormService
     *
     * @return Response
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2022-07-01
     */
    #[Route('/balancer', name: 'updateEnableBalancer', methods: ['PUT'])]
    public function updateEnableBalancer(
        Request $Request,
        jsonResponseService $json,
        Connection $connection,
        PqrFormService $PqrFormService,
    ): Response {
        $connection->beginTransaction();
        try {
            $status = $Request->request->get('enable_balancer', 0);

            $success = $PqrFormService->save([
                'enable_balancer' => $status,
            ]);

            if (!$success) {
                throw new ValidationFailedException($PqrFormService->getErrorManager()->getMessage());
            }

            $data = $PqrFormService->getDataPqrForm();
            $connection->commit();

            return $json->success($data);
        } catch (Throwable $th) {
            $connection->rollBack();

            return $json->exception($th);
        }
    }

    #[Route('/consecutiveDays', name: 'updateEnableConsecutiveDays', methods: ['PUT'])]
    public function updateEnableConsecutiveDays(
        Request $Request,
        jsonResponseService $json,
        Connection $connection,
        PqrFormService $PqrFormService,
    ): Response {
        $connection->beginTransaction();
        try {
            $status = $Request->request->get('enable_con_days', 0);

            $success = $PqrFormService->save([
                'enable_con_days' => $status,
            ]);

            if (!$success) {
                throw new ValidationFailedException($PqrFormService->getErrorManager()->getMessage());
            }

            $data = $PqrFormService->getDataPqrForm();
            $connection->commit();

            return $json->success($data);
        } catch (Throwable $th) {
            $connection->rollBack();

            return $json->exception($th);
        }
    }

    #[Route('/descriptionField', name: 'descriptionField', methods: ['PUT'])]
    public function descriptionField(
        jsonResponseService $json,
        Request $Request,
        Connection $connection,
        TranslatorInterface $translator,
        PqrFormService $PqrFormsService,
    ): Response {
        $connection->beginTransaction();

        try {
            $fieldId = $Request->request->get('fieldId');

            if (!$fieldId) {
                $message = $translator->trans('indicar_identificador_campo_descripcion');
                throw new MissingParameterException($message);
            }

            if (!$PqrFormsService->updateFieldDescription((int)$fieldId)) {
                throw new ValidationFailedException($PqrFormsService->getErrorManager()->getMessage());
            }

            $connection->commit();

            return $json->success();
        } catch (Throwable $th) {
            $connection->rollBack();

            return $json->exception($th);
        }
    }

    #[Route('/receivingchannels', name: 'receivingchannels', methods: ['PUT'])]
    public function receivingchannels(
        jsonResponseService $json,
        Request $Request,
        Connection $connection,
        TranslatorInterface $translator,
        PqrFormService $PqrFormsService,
    ): Response {
        $connection->beginTransaction();

        try {
            $channels = $Request->request->all('channels');

            if (!$channels) {
                $trans = $translator->trans("indicar_canales_recepcion");
                throw new MissingParameterException($trans);
            }

            if (!$PqrFormsService->save([
                'canal_recepcion' => json_encode($channels),
            ])) {
                throw new ValidationFailedException($PqrFormsService->getErrorManager()->getMessage());
            }

            $connection->commit();

            return $json->success();
        } catch (Throwable $th) {
            $connection->rollBack();

            return $json->exception($th);
        }
    }

    /**
     * Crea o edita la funciones utilizadas para filtros sobre los reportes de PQR
     *
     * @param string $functionName
     * @param int    $status
     *
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2022-07-01
     */
    private function editOrCreateFunction(string $functionName, int $status): void
    {
        $Funcion = Funcion::findByAttributes([
            'nombre' => $functionName,
        ]);

        if ($Funcion) {
            $Funcion->getService()->save([
                'estado' => $status,
            ]);
        } elseif ($status) {
            $FuncionService = (new Funcion())->getService();
            $FuncionService->save([
                'nombre' => $functionName,
                'estado' => $status,
                'fecha'  => date('Y-m-d H:i:s'),
            ]);
        }
    }
}
