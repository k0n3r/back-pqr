<?php

namespace App\Bundles\pqr\Services\Customizable;

use App\services\models\formatos\Customizable;
use Saia\controllers\documento\SaveFt;
use Saia\controllers\generator\component\Rad;
use Saia\models\formatos\CampoOpciones;

class PqrCustomizable implements Customizable
{

    /**
     * @param string $methodName
     * @param array  $params
     * @return bool|void
     * @author Andres Agudelo <andres.agudelo@cerok.com> 2022-10-20
     */
    public function execute(string $methodName, array $params = [])
    {
        switch ($methodName) {
            case 'hasINeedMoreFields':
                return false;

            case 'fastFiling':
                $Documento = $params[0];
                $data = $params[1];

                $CampoOpcionColilla = CampoOpciones::getFieldOptions(
                    $Documento->getFormat(),
                    Rad::COLILLA,
                    $data[Rad::COLILLA]
                );

                $SaveFt = new SaveFt($Documento);
                $SaveFt->edit([
                    'descripcion' => $data['descripcion'],
                    'asunto'      => $data['descripcion'],
                    'sys_folios'  => $data[Rad::NUMERO_FOLIOS],
                    'colilla'     => $CampoOpcionColilla?->getPK()
                ]);

                return true;
        }
    }
}