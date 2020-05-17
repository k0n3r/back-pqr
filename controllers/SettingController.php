<?php

namespace Saia\Pqr\controllers;

class SettingController extends Controller
{
    const  DIRECTORY_PQR = 'ws/pqr/';
    const DIRECTORY_CLASIFICACION = 'ws/calificacion/';

    public function __construct(array $request = null)
    {
        $this->request = $request;
    }

    public function getSetting(): object
    {
        $dataForm = PqrFormController::index();
        $dataFields = PqrFormFieldController::index();

        $Response = (object) [
            'success' => 1,
            'data' => [
                'formName' => $dataForm->data['label'],
                'fields' => $dataFields->data,
                'urlWs' => PROTOCOLO_CONEXION . DOMINIO . '/' . self::DIRECTORY_PQR,
                'showAnonymous' => (int) $dataForm->data['anonymous']
            ]
        ];

        return $Response;
    }
}
