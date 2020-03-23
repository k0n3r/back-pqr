<?php

namespace Saia\Pqr\formatos\pqr_respuesta;

class FtPqrRespuesta extends FtPqrRespuestaProperties
{
    public function __construct($id = null)
    {
        parent::__construct($id);
    }

    /**
     * Carga el mostrar de la respuesta a la PQR
     *
     * @return string
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function showTemplate(): string
    {
        return $this->content;
    }

    /**
     * Seteo la funcion principal y devuelvo solo
     * los parametros necesarios al editar
     *
     * @return void
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function getRouteParams(string $scope): array
    {
        $data = [];
        if ($scope == self::SCOPE_ROUTE_PARAMS_EDIT) {
            $data = [
                'numero' => (int) $this->Documento->numero
            ];
        }
        return $data;
    }
}
