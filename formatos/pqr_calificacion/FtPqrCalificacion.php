<?php

namespace Saia\Pqr\formatos\pqr_calificacion;

class FtPqrCalificacion extends FtPqrCalificacionProperties
{

    public function showCalification()
    {
        $fields = $this->getFormat()->getFields();

        $code = "<table class='table table-bordered' style='width:100%'>";
        foreach ($fields as $CamposFormato) {
            if ($CamposFormato->isSystemField()) {
                continue;
            }
            $answer = $this->getFieldValue($CamposFormato->nombre);
            $text = $CamposFormato->etiqueta;

            $code .= "<tr>
                <td class='text-uppercase'>{$text}: <br/><strong>{$answer}</strong></td>
            <tr>";
        }
        $code .= '</table>';

        return $code;
    }
}
