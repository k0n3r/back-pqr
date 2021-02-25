<?php

namespace App\Bundles\pqr\formatos\pqr_calificacion;

class FtPqrCalificacion extends FtPqrCalificacionProperties
{

    public function showCalification(): string
    {
        $fields = $this->getFormat()->getFields();

        $code = '<table class="table table-bordered" style="width:100%">';
        foreach ($fields as $CamposFormato) {
            if ($CamposFormato->isSystemField()) {
                continue;
            }
            $answer = $this->getFieldValue($CamposFormato->nombre);
            $text = $CamposFormato->etiqueta;

            $code .= '<tr>
                <td>' . mb_strtoupper($text) . ': <br/><strong>' . mb_strtoupper($answer) . '</strong><br/><br/></td>
            </tr>';
        }
        $code .= '</table>';

        return $code;
    }
}
