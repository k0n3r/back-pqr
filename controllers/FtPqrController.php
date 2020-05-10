<?php

namespace Saia\Pqr\controllers;

use Saia\Pqr\formatos\pqr\FtPqr;
use Saia\Pqr\models\PqrResponseTemplate;

class FtPqrController
{

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
     * @var FtPqr
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public $FtPqr;


    public function __construct(array $request = null)
    {
        $this->request = $request;
    }

    /**
     * Obtiene los datos de la PQR
     *
     * @return object
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function index(): object
    {
        $Response = (object) [
            'success' => 0,
            'data' => []
        ];
        if ($id = $this->request['id']) {
            if ($FtPqr = FtPqr::findByDocumentId($id)) {
                $Response->success = 1;
                $Response->data = $FtPqr->getAttributes();
            }
        }

        return $Response;
    }


    /**
     * Obtiene el email
     *
     * @return object
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function getEmail(): object
    {
        $Response = (object) [
            'success' => 0,
            'data' => []
        ];
        if ($id = $this->request['id']) {
            if ($FtPqr = FtPqr::findByDocumentId($id)) {
                $Response->success = 1;
                $Response->data = $FtPqr->sys_email;
            }
        }

        return $Response;
    }


    /**
     * Obtiene el contenido de la plantilla
     *
     * @return object
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function getPlantilla(): object
    {
        $Response = (object) [
            'success' => 0,
            'data' => []
        ];
        if ($id = $this->request['id']) {
            if ($PqrResponseTemplate = new PqrResponseTemplate($id)) {
                $Response->success = 1;
                $Response->data = $PqrResponseTemplate->content;
            }
        }

        return $Response;
    }
}
