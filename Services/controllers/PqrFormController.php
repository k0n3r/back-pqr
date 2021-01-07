<?php

namespace App\Bundles\pqr\Services\controllers;

use App\Bundles\pqr\Services\models\PqrForm;
use Saia\core\DatabaseConnection;
use App\Bundles\pqr\Services\models\PqrFormField;
use App\Bundles\pqr\Services\PqrFormService;
use App\Bundles\pqr\Services\controllers\AddEditFormat\AddEditFtPqr;

class PqrFormController extends Controller
{
    const URLWSPQR = PROTOCOLO_CONEXION . DOMINIO . '/ws/pqr/';
    const URLWSCALIFICACION = PROTOCOLO_CONEXION . DOMINIO . '/ws/pqr_calificacion/';

    private PqrForm $PqrForm;

    public function __construct(array $request = null)
    {
        parent::__construct($request);
        $this->PqrForm = PqrForm::getPqrFormActive();
    }

    /**
     * Obtiene todos los datos del modulo de configuracion
     *
     * @return object
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function getSetting(): object
    {
        $PqrFormService = new PqrFormService($this->PqrForm);

        $Response = (object) [
            'success' => 1,
            'data' => [
                'urlWs' => self::URLWSPQR,
                'publish' => $this->PqrForm->fk_formato ? 1 : 0,
                'pqrForm' => $PqrFormService->getDataPqrForm(),
                'pqrTypes' => $PqrFormService->getTypes(),
                'pqrFormFields' => $PqrFormService->getDataPqrFormFields(),
                'pqrNotifications' => $PqrFormService->getDataPqrNotifications(),
                'optionsNotyMessages' => $PqrFormService->getDataPqrNotyMessages()
            ]
        ];

        return $Response;
    }

    /**
     * Actualiza los dias de vencimientos de los tipo de PQR
     *
     * @return object
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function updatePqrTypes(): object
    {
        $Response = (object) [
            'success' => 0
        ];

        try {
            $conn = DatabaseConnection::getDefaultConnection();
            $conn->beginTransaction();

            $PqrFormField = $this->PqrForm->getRow('sys_tipo');
            $PqrFormField->setAttributes([
                'setting' => json_encode($this->request)
            ]);
            $PqrFormField->update();

            if ($PqrFormField->fk_campos_formato) {
                AddEditFtPqr::addEditformatOptions($PqrFormField);
            }

            $Response->success = 1;
            $conn->commit();
        } catch (\Exception $th) {
            $conn->rollBack();
            $Response->message = $th->getMessage();
        }

        return $Response;
    }

    /**
     * Habilita/deshabilita la radicacion por Email
     *
     * @return object
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function updatePqrForm(): object
    {
        $Response = (object) [
            'success' => 0
        ];

        try {
            $conn = DatabaseConnection::getDefaultConnection();
            $conn->beginTransaction();

            $this->PqrForm->setAttributes($this->request['pqrForm']);
            if (!$this->PqrForm->update()) {
                throw new \Exception("No fue posible actualizar", 200);
            };
            $PqrFormService = new PqrFormService($this->PqrForm);

            $Response->pqrForm = $PqrFormService->getDataPqrForm();
            $Response->success = 1;
            $conn->commit();
        } catch (\Exception $th) {
            $conn->rollBack();
            $Response->message = $th->getMessage();
        }

        return $Response;
    }

    /**
     * Actualiza los datos de configuracion del formulario
     *
     * @return object
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function updateSetting(): object
    {
        $Response = (object) [
            'success' => 0
        ];

        try {
            $conn = DatabaseConnection::getDefaultConnection();
            $conn->beginTransaction();

            DatabaseConnection::getDefaultConnection()
                ->createQueryBuilder()
                ->update('pqr_form_fields')
                ->set('anonymous', 0)
                ->set('required_anonymous', 0)
                ->where("name<>'sys_tipo'")->execute();


            $this->PqrForm->setAttributes($this->request['pqrForm']);
            if (!$this->PqrForm->update()) {
                throw new \Exception("No fue posible actualizar", 200);
            };

            if ($this->PqrForm->show_anonymous) {
                if ($formFields = $this->request['formFields']) {
                    foreach ($formFields['dataShowAnonymous'] as $id) {
                        $PqrFormField = new PqrFormField($id);
                        $PqrFormField->anonymous = 1;
                        if ($dataRequired = $formFields['dataRequiredAnonymous']) {
                            if (in_array($id, $dataRequired)) {
                                $PqrFormField->required_anonymous = 1;
                            }
                        }
                        if (!$PqrFormField->update()) {
                            throw new \Exception("No fue posible actualizar", 200);
                        };
                    }
                }
            }

            $PqrFormService = new PqrFormService($this->PqrForm);
            $Response->data = [
                'pqrForm' => $PqrFormService->getDataPqrForm(),
                'pqrFormFields' => $PqrFormService->getDataPqrFormFields(),
            ];
            $Response->success = 1;
            $conn->commit();
        } catch (\Exception $th) {
            $conn->rollBack();
            $Response->message = $th->getMessage();
        }

        return $Response;
    }

    /**
     * Actualiza la configuracion para la respuesta
     *
     * @return object
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function updateResponseConfiguration(): object
    {
        $Response = (object) [
            'success' => 0
        ];

        try {
            $conn = DatabaseConnection::getDefaultConnection();
            $conn->beginTransaction();
            $data = [];
            foreach ($this->request['tercero'] as $name => $value) {
                $data[] = [
                    'name' => $name,
                    'value' => $value
                ];
            }

            $this->PqrForm->response_configuration = json_encode(['tercero' => $data]);
            if (!$this->PqrForm->update()) {
                throw new \Exception("No fue posible actualizar", 200);
            };

            $Response->success = 1;
            $conn->commit();
        } catch (\Exception $th) {
            $conn->rollBack();
            $Response->message = $th->getMessage();
        }

        return $Response;
    }

    /**
     * Obtiene la configuracion de la respuesta
     *
     * @return object
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function getResponseConfiguration(): object
    {
        return (object) [
            'success' => 1,
            'data' => $this->PqrForm->getResponseConfiguration(true) ?? []
        ];
    }
}
