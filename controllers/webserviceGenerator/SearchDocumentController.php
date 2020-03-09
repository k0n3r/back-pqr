<?php

namespace Saia\Pqr\Controllers;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use Saia\core\DatabaseConnection;
use Saia\models\documento\Documento;

class SearchDocumentController
{
    /**
     * Variable que contiene todo el request que llega de las peticiones
     *
     * @var array|null
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public $request;

    public function __construct(array $request = null)
    {
        $this->request = $request;
    }

    /**
     * Busca un documento, utilizado en los select2
     *
     * @return object
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function search(): object
    {
        $Response = (object) [
            'success' => 1,
            'data' => []
        ];


        $estados = Documento::$InactiveStates;
        array_push($estados, Documento::ACTIVO);

        $Qb = DatabaseConnection::getQueryBuilder()
            ->select('*')
            ->from('documento')
            ->where("estado NOT IN (:estados)")
            ->setParameter(':estados', $estados, Connection::PARAM_STR_ARRAY)
            ->andWhere("numero like :radicado")
            ->setParameter(':radicado',  $this->request['radicado'] . '%', Type::getType('string'))
            ->orderBy('fecha', 'desc');

        $data = [];
        if ($records = Documento::findByQueryBuilder($Qb)) {
            foreach ($records as $Documento) {
                $Formato = $Documento->getFormat();
                $data[] = [
                    'id' => $Documento->getPK(),
                    'text' => "Radicado: {$Documento->numero} - {$Formato->etiqueta}"
                ];
            }
        }

        $Response->data = $data;

        return $Response;
    }
}
