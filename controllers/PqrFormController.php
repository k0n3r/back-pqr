<?php

namespace Saia\Pqr\Controllers;

use Exception;
use Saia\models\Contador;
use Saia\Pqr\Models\PqrForm;
use Saia\core\DatabaseConnection;
use Saia\models\formatos\Formato;
use Saia\Pqr\Models\PqrFormField;
use Saia\Pqr\Models\PqrHtmlField;
use Saia\Pqr\Controllers\WebservicePqr;
use Saia\Pqr\Controllers\WebserviceCalificacion;
use Saia\Pqr\Controllers\AddEditFormat\AddEditFormat;
use Saia\Pqr\Controllers\AddEditFormat\IAddEditFormat;
use Saia\Pqr\Controllers\AddEditFormat\TAddEditFormat;
use Saia\Pqr\Controllers\AddEditFormat\FtPqrController;


class PqrFormController
{
    use TAddEditFormat;

    /**
     * Variable que contiene todo el request que llega de las peticiones
     *
     * @var array|null
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public $request;
    /**
     *
     * @var PqrForm
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public $PqrForm;


    public function __construct(array $request = null)
    {
        $this->request = $request;
    }

    /**
     * Obtiene el formulario activo
     *
     * @return object
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function index(): object
    {
        $Response = (object) [
            'success' => 1,
            'data' => []
        ];

        if ($PqrForm = PqrForm::getPqrFormActive()) {
            $Response->data = $PqrForm->getAttributes();
        };

        return $Response;
    }

    /**
     * Almacena un nuevo formulario
     *
     * @return object
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function store(): object
    {
        $Response = (object) [
            'success' => 1,
            'data' => []
        ];
        $params = $this->request['params'];

        try {
            $conn = DatabaseConnection::beginTransaction();

            if (!$contador = Contador::findColumn('idcontador', [
                'nombre' => 'radicacion_entrada'
            ])) {
                throw new Exception("El contador Externo-Interno NO existe", 1);
            }

            $nameFormat = 'pqr';
            $defaultFields = [
                'fk_formato' => 0,
                'active' => 1,
                'name' => $nameFormat,
                'fk_contador' => $contador[0]
            ];

            $attributes = array_merge($params, $defaultFields);

            $this->PqrForm = new PqrForm();
            $this->PqrForm->setAttributes($attributes);
            if ($this->PqrForm->save()) {
                $this->createSystemFields();
                $conn->commit();
                $Response->data = $this->PqrForm->getAttributes();
            } else {
                throw new Exception("No fue posible crear el formulario", 1);
            }
        } catch (Exception $th) {
            $conn->rollBack();
            $Response->success = 0;
            $Response->message = $th->getMessage();
        }

        return $Response;
    }

    /**
     * Crea los campos del sistema
     *
     * @param PqrForm $PqrForm
     * @return void
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     * 
     */
    protected function createSystemFields(): void
    {
        foreach ($this->getSystemFields() as  $field) {
            PqrFormField::newRecord($field);
        }
    }

    /**
     * Campos que siempre deben ir en el formulario
     *
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     * 
     * @throws Exception
     * 
     */
    protected function getSystemFields(): array
    {

        if ($record = PqrHtmlField::findColumn('id', ['type' => 'select'])) {
            $selectType = $record[0];
        } else {
            throw new Exception("No se encontro el tipo de campo Select", 1);
        }

        if ($record = PqrHtmlField::findColumn('id', ['type' => 'email'])) {
            $emailType = $record[0];
        } else {
            throw new Exception("No se encontro el tipo de campo Input", 1);
        }

        return [
            [
                'label' => 'Tipo',
                'name' => 'sys_tipo',
                'required' => 1,
                'system' => 1,
                'orden' => 2,
                'fk_pqr_html_field' => $selectType,
                'fk_pqr_form' => $this->PqrForm->getPK(),
                'setting' => json_encode([
                    'options' => [
                        'Petición',
                        'Queja',
                        'Reclamo',
                        'Sugerencia',
                        'Felicitación'
                    ]
                ])
            ],
            [
                'label' => 'E-mail',
                'name' => 'sys_email',
                'required' => 1,
                'system' => 1,
                'orden' => 3,
                'fk_pqr_html_field' => $emailType,
                'fk_pqr_form' => $this->PqrForm->getPK(),
                'setting' => json_encode([
                    'placeholder' => 'example@pqr.com'
                ])
            ]
        ];
    }

    /**
     * Actualiza el formulario
     *
     * @return object
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function update(): object
    {
        $Response = (object) [
            'success' => 0
        ];

        $params = $this->request['params']['data'];
        $id = $this->request['params']['id'];

        try {
            $conn = DatabaseConnection::beginTransaction();

            $PqrForm = new PqrForm($id);
            $PqrForm->setAttributes($params);
            if ($PqrForm->update()) {

                $conn->commit();
                $Response->success = 1;
                $Response->data = $PqrForm->getAttributes();
            } else {
                throw new Exception("No fue posible actualizar el formulario", 1);
            }
        } catch (Exception $th) {
            $conn->rollBack();
            $Response->success = 0;
            $Response->message = $th->getMessage();
        }

        return $Response;
    }

    /**
     * publica o crea el formulario en el webservice
     *
     * @return object
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function publish(): object
    {
        $Response = (object) [
            'success' => 1,
            'data' => []
        ];

        try {
            $conn = DatabaseConnection::beginTransaction();

            $this->PqrForm = PqrForm::getPqrFormActive();

            $option = $this->PqrForm->fk_formato ? AddEditFormat::ADIT : AddEditFormat::ADD;
            $this->addEditFormat(
                new FtPqrController($this->PqrForm),
                $option
            );

            if (!$FormatoR = Formato::findByAttributes([
                'nombre' => 'pqr_respuesta'
            ])) {
                throw new Exception("El formato de respuesta PQR no fue encontrado", 1);
            }
            $this->FormatGenerator($FormatoR->getPK());

            if (!$FormatoC = Formato::findByAttributes([
                'nombre' => 'pqr_calificacion'
            ])) {
                throw new Exception("El formato de calificacion PQR no fue encontrado", 1);
            }
            $this->FormatGenerator($FormatoC->getPK());

            $this->generateView();

            $Web = new WebservicePqr($this->PqrForm);
            $Web->generate();

            $WebCal = new WebserviceCalificacion($FormatoC);
            $WebCal->generate();

            $Response->data = $this->PqrForm->getAttributes();
            $conn->commit();
        } catch (\Throwable $th) {
            var_dump($th);
            $conn->rollBack();
            $Response->success = 0;
            $Response->message = $th->getMessage();
        }

        return $Response;
    }

    /**
     * Encargado de genera el formato recibido
     *
     * @param IAddEditFormat $Controller
     * @param string $addEdit
     * @return void
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    protected function addEditFormat(IAddEditFormat $Controller, string $addEdit): void
    {
        $Generate = new AddEditFormat($Controller, $addEdit);
        $Generate->generate();
    }

    /**
     * Genera las vista del proceso
     *
     * @return void
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    protected function generateView(): void
    {
        $this->viewPqr();
        $this->viewRespuestaPqr();
    }

    /**
     * Genera el SQL de la vista PQR
     *
     * @return void
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    protected function viewPqr()
    {
        $sql = "SELECT d.iddocumento,d.numero,d.fecha,ft.sys_email,ft.sys_tipo,ft.sys_estado,ft.idft_pqr as idft
        FROM ft_pqr ft,documento d
        WHERE ft.documento_iddocumento=d.iddocumento
        AND d.estado NOT IN ('ELIMINADO','ANULADO')";

        $this->createView('vpqr', $sql);
    }

    /**
     * Genera el SQL de la vista respuesta a la PQR
     *
     * @return void
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    protected function viewRespuestaPqr()
    {
        $sql = "SELECT d.iddocumento,d.numero,d.fecha,ft.idft_pqr_respuesta as idft,ft.ft_pqr
        FROM ft_pqr_respuesta ft,documento d
        WHERE ft.documento_iddocumento=d.iddocumento AND d.estado NOT IN ('ELIMINADO')";

        $this->createView('vpqr_respuesta', $sql);
    }

    /**
     * Crea la vista en la DB
     *
     * @param string $select
     * @return void
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    protected function createView(string $name, string $select): void
    {
        $conn = DatabaseConnection::getInstance();

        switch (MOTOR) {
            case 'MySql':
            case 'Oracle':
                $create = "CREATE OR REPLACE VIEW {$name} AS {$select}";
                $conn->executeQuery($create);
                break;

            case 'SqlServer':
                $drop = "DROP VIEW {$name}";
                $conn->executeQuery($drop);


                $create = "CREATE VIEW {$name} AS {$select}";
                $conn->executeQuery($create);

                break;

            default:
                throw new Exception("No fue posible generar la vista {$name}", 1);
                break;
        }
    }
}
