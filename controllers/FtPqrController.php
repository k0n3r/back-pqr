<?php

namespace Saia\Pqr\Controllers;

use Saia\Pqr\formatos\pqr\FtPqr;

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
            'success' => 1,
            'data' => []
        ];
        if ($id = $this->request['id']) {
            $FtPqr = new FtPqr($id);
            $Response->data = $FtPqr->getAttributes();
        }


        return $Response;
    }
}
