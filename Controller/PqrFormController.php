<?php

namespace App\Bundles\pqr\Controller;

use App\Bundles\pqr\Services\FtPqrService;
use App\services\exception\SaiaException;
use Doctrine\DBAL\Connection;
use Exception;
use App\Bundles\pqr\Services\PqrService;
use App\services\response\ISaiaResponse;
use App\Bundles\pqr\Services\models\PqrForm;
use Saia\models\formatos\CategoriaFormato;
use Saia\models\formatos\Formato;
use Saia\models\funcion\Funcion;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Bundles\pqr\Services\models\PqrFormField;
use App\Bundles\pqr\Services\PqrFormFieldService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Throwable;

/**
 * @Route("/form", name="form_")
 */
class PqrFormController extends AbstractController
{

    /**
     * @Route("/textFields", name="getTextFields", methods={"GET"})
     */
    public function getTextFields(
        ISaiaResponse $saiaResponse
    ): Response {

        try {
            $saiaResponse->replaceData(PqrService::getTextFields());
            $saiaResponse->setSuccess(1);
        } catch (Throwable $th) {
            $saiaResponse->setMessage($th->getMessage());
        }

        return $saiaResponse->getResponse();
    }

    /**
     * @Route("/publish", name="publish", methods={"PUT"})
     */
    public function publish(
        ISaiaResponse $saiaResponse,
        Connection $Connection
    ): Response {

        $Connection->beginTransaction();
        try {

            $PqrFormService = (PqrForm::getInstance())->getService();
            if (!$PqrFormService->publish()) {
                throw new SaiaException(
                    $PqrFormService->getErrorManager()->getMessage(),
                    $PqrFormService->getErrorManager()->getCode(),
                );
            }

            $data = [
                'pqrForm'       => $PqrFormService->getDataPqrForm(),
                'pqrFormFields' => $PqrFormService->getDataPqrFormFields(),
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
     * @Route("/setting", name="getSetting", methods={"GET"})
     */
    public function getSetting(
        ISaiaResponse $saiaResponse
    ): Response {

        try {
            $data = (PqrForm::getInstance())->getService()
                ->getSetting();

            $saiaResponse->replaceData($data);
            $saiaResponse->setSuccess(1);
        } catch (Throwable $th) {
            $saiaResponse->setMessage($th->getMessage());
        }
        return $saiaResponse->getResponse();
    }

    /**
     * @Route("/responseSetting", name="getResponseSetting", methods={"GET"})
     */
    public function getResponseSetting(
        ISaiaResponse $saiaResponse
    ): Response {

        try {
            $data = (PqrForm::getInstance())
                    ->getResponseConfiguration(true) ?? [];

            $saiaResponse->replaceData($data);
            $saiaResponse->setSuccess(1);
        } catch (Throwable $th) {
            $saiaResponse->setMessage($th->getMessage());
        }

        return $saiaResponse->getResponse();
    }

    /**
     * @Route("/sortFields", name="sortFields", methods={"PUT"})
     */
    public function sortFields(
        Request $request,
        ISaiaResponse $saiaResponse,
        Connection $Connection
    ): Response {

        $Connection->beginTransaction();
        try {

            foreach ($request->get('fieldOrder') as $record) {
                $PqrFormFieldService = (new PqrFormField($record['id']))->getService();
                $status = $PqrFormFieldService->update([
                    'orden' => $record['order'] + PqrFormFieldService::INITIAL_ORDER
                ]);

                if (!$status) {
                    throw new Exception("No fue posible actualizar el orden", 1);
                }
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
     * @Route("/updateSetting", name="updateSetting", methods={"PUT"})
     */
    public function updateSetting(
        Request $request,
        ISaiaResponse $saiaResponse,
        Connection $Connection
    ): Response {

        $Connection->beginTransaction();
        try {

            $PqrFormService = (PqrForm::getInstance())->getService();
            if (!$PqrFormService->updateSetting($request->get('data'))) {
                throw new Exception(
                    $PqrFormService->getErrorManager()->getMessage(),
                    $PqrFormService->getErrorManager()->getCode(),
                );
            }

            $data = [
                'pqrForm'       => $PqrFormService->getDataPqrForm(),
                'pqrFormFields' => $PqrFormService->getDataPqrFormFields(),
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
     * @Route("/updateResponseSetting", name="updateResponseSetting", methods={"PUT"})
     */
    public function updateResponseSetting(
        Request $request,
        ISaiaResponse $saiaResponse,
        Connection $Connection
    ): Response {

        $Connection->beginTransaction();
        try {

            $PqrFormService = (PqrForm::getInstance())->getService();
            if (!$PqrFormService->updateResponseSetting($request->get('data'))) {
                throw new Exception(
                    $PqrFormService->getErrorManager()->getMessage(),
                    $PqrFormService->getErrorManager()->getCode(),
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
     * @Route("/updateShowReport", name="updateShowReport", methods={"PUT"})
     */
    public function updateShowReport(
        Request $request,
        ISaiaResponse $saiaResponse,
        Connection $Connection
    ): Response {

        $Connection->beginTransaction();
        try {

            $Connection->createQueryBuilder()
                ->update('pqr_form_fields')
                ->set('show_report', 0)->execute();

            if ($request->get('ids')) {
                foreach ($request->get('ids') as $id) {
                    $PqrFormFieldService = (new PqrFormField($id))->getService();
                    if (!$PqrFormFieldService->update([
                        'show_report' => 1
                    ])) {
                        throw new Exception("No fue posible actualizar", 200);
                    }
                }
            }

            $PqrFormService = (PqrForm::getInstance())->getService();
            $PqrFormService->generaReport();
            $data = $PqrFormService->getDataPqrFormFields();

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
     * Acutaliza el campo mostrar/ocultar campos vacios
     * @Route("/showEmpty", name="updateShowEmpty", methods={"PUT"})
     *
     * @param Request       $Request
     * @param ISaiaResponse $saiaResponse
     * @param Connection    $Connection
     * @return Response
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2021-10-05
     */
    public function updateShowEmpty(
        Request $Request,
        ISaiaResponse $saiaResponse,
        Connection $Connection
    ): Response {

        $Connection->beginTransaction();
        try {

            $PqrFormService = (PqrForm::getInstance())->getService();
            $success = $PqrFormService->save([
                'show_empty' => $Request->get('show_empty', 1)
            ]);
            if (!$success) {
                throw new SaiaException($PqrFormService->getErrorManager()->getMessage());
            }

            $saiaResponse->replaceData($PqrFormService->getDataPqrForm());
            $saiaResponse->setSuccess(1);
            $Connection->commit();
        } catch (Throwable $th) {
            $Connection->rollBack();
            $saiaResponse->setMessage($th->getMessage());
        }

        return $saiaResponse->getResponse();
    }

    /**
     * Habilita la radicacion por correo
     * @Route("/radEmail", name="updateRadEmail", methods={"PUT"})
     *
     * @param Request       $Request
     * @param ISaiaResponse $saiaResponse
     * @param Connection    $Connection
     * @return Response
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2021-11-15
     */
    public function updateRadEmail(
        Request $Request,
        ISaiaResponse $saiaResponse,
        Connection $Connection
    ): Response {

        $Connection->beginTransaction();
        try {
            $enable = $Request->get('rad_email', 0);
            $PqrFormService = (PqrForm::getInstance())->getService();

            if ($enable) {
                $FormatoRound = Formato::findByAttributes([
                    'module' => 'roundcube'
                ]);
                if (!$FormatoRound) {
                    throw new SaiaException('El modulo de Roudcube/Factura no esta instalado!');
                }

                $Formato = Formato::findByAttributes([
                    'nombre' => 'pqr'
                ]);
                if ($Formato) {
                    $Formato->getService()->save(array_merge($Formato->getAttributes(), [
                        'fk_categoria_formato' => CategoriaFormato::RADICACION
                    ]));
                }
                $PqrFormField = PqrFormField::findByAttributes([
                    'name' => 'sys_anexos'
                ]);

                if ($PqrFormField) {
                    $PqrFormField->getService()->save([
                        'active' => 1
                    ]);
                }
                $PqrFormService->publish();
            }

            $success = $PqrFormService->save([
                'rad_email' => $enable
            ]);

            if (!$success) {
                throw new SaiaException($PqrFormService->getErrorManager()->getMessage());
            }

            $saiaResponse->replaceData($PqrFormService->getDataPqrForm());
            $saiaResponse->setSuccess(1);
            $Connection->commit();
        } catch (Throwable $th) {
            $Connection->rollBack();
            $saiaResponse->setMessage($th->getMessage());
        }

        return $saiaResponse->getResponse();
    }


    /**
     * Habilita y aplica el filtro por dependencia a los reportes
     * @Route("/filterReport", name="updateEnableFilterDep", methods={"PUT"})
     *
     * @param Request       $Request
     * @param ISaiaResponse $saiaResponse
     * @param Connection    $Connection
     * @return Response
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2022-07-01
     */
    public function updateEnableFilterDep(
        Request $Request,
        ISaiaResponse $saiaResponse,
        Connection $Connection
    ): Response {

        $Connection->beginTransaction();
        try {

            $status = $Request->get('enable_filter_dep', 0);

            $PqrForm = PqrForm::getInstance();
            $PqrFormService = $PqrForm->getService();

            if ($status && !$PqrForm->getRow('sys_dependencia')) {
                throw new SaiaException("Debe agregar al formulario el componente de Dependencia");
            }

            $this->editOrCreateFunction(FtPqrService::FUNCTION_ADMIN_PQR, $status);
            $this->editOrCreateFunction(FtPqrService::FUNCTION_ADMIN_DEP_PQR, $status);


            $success = $PqrFormService->save([
                'enable_filter_dep' => $status
            ]);

            if (!$success) {
                throw new SaiaException($PqrFormService->getErrorManager()->getMessage());
            }

            $saiaResponse->replaceData($PqrFormService->getDataPqrForm());
            $saiaResponse->setSuccess(1);
            $Connection->commit();
        } catch (Throwable $th) {
            $Connection->rollBack();
            $saiaResponse->setMessage($th->getMessage());
        }

        return $saiaResponse->getResponse();
    }

    /**
     * Crea o edita la funciones utilizadas para filtros sobre los reportes de PQR
     *
     * @param string $functionName
     * @param int    $status
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2022-07-01
     */
    private function editOrCreateFunction(string $functionName, int $status): void
    {
        $Funcion = Funcion::findByAttributes([
            'nombre' => $functionName
        ]);

        if ($Funcion) {
            $Funcion->getService()->save([
                'estado' => $status
            ]);
        } elseif ($status) {
            $FuncionService = (new Funcion())->getService();
            $FuncionService->save([
                'nombre' => $functionName,
                'estado' => $status,
                'fecha'  => date('Y-m-d H:i:s')
            ]);
        }
    }
}
