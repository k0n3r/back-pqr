<?php

use Saia\models\formatos\CampoSeleccionados;

/**
 * Obtiene el filtro por las calificaciones
 *
 * @return string
 * @author Andres Agudelo <andres.agudelo@cerok.com>
 * @date   2020
 */
function filter_calificacion(): string
{
    if ($_REQUEST['variable_busqueda']) {
        $params = json_decode($_REQUEST['variable_busqueda'], true);
        $idft = $params['idft_pqr_respuesta'];
        if ($idft) {
            return "ft_pqr_respuesta=$idft";
        }
    }
    return '1=1';
}

/**
 * Obtiene la calificacion sobre la gestion
 *
 * @param integer $iddocumento
 * @param integer $fkCampoOpciones
 * @return string
 * @author Andres Agudelo <andres.agudelo@cerok.com>
 * @date   2020
 */
function getGestion(int $iddocumento, int $fkCampoOpciones): string
{
    $calGestion = '';
    if ($valor = CampoSeleccionados::findColumn('valor', [
        'fk_campo_opciones' => $fkCampoOpciones,
        'fk_documento' => $iddocumento
    ])) {
        $calGestion = $valor[0];
    }

    return $calGestion;
}

/**
 * Obtiene la calificacion sobre el servicio
 *
 * @param integer $iddocumento
 * @param integer $fkCampoOpciones
 * @return string
 * @author Andres Agudelo <andres.agudelo@cerok.com>
 * @date   2020
 */
function getServicio(int $iddocumento, int $fkCampoOpciones): string
{
    $calExperiencia = '';
    if ($valor = CampoSeleccionados::findColumn('valor', [
        'fk_campo_opciones' => $fkCampoOpciones,
        'fk_documento' => $iddocumento
    ])) {
        $calExperiencia = $valor[0];
    }

    return $calExperiencia;
}
