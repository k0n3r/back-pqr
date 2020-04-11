<?php
$max_salida = 10;
$rootPath = $ruta = '';

while ($max_salida > 0) {
    if (is_file($ruta . 'sw.js')) {
        $rootPath = $ruta;
    }

    $ruta .= '../';
    $max_salida--;
}

include_once $rootPath . 'views/assets/librerias.php';

//TODO: ERROR EN BASEURL
$baseUrl = "../../";

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>SAIA - SGDEA</title>
</head>

<body>
    <div class="container">
        <div class="row">
            <div class="col-12">
                <form id="find_document_form">
                    <input type="hidden" id="variable_busqueda" name="variable_busqueda">
                    <input type="hidden" name="idbusqueda_componente" id="component" value="<?= $_REQUEST['idbusqueda_componente'] ?>">

                    <div class="row">
                        <div class="col-12">
                            <div class="form-group form-group-default">
                                <label>Radicado:</label>
                                <input class="form-control" name="bqCampo_numero" type="number">
                                <input type="hidden" name="bqCondicional_numero" value="like">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="form-group form-group-default form-group-default-select2">
                                <label>Fecha:</label>
                                <select class="full-width" id="filtro_fecha">
                                    <option value="1">Seleccione</option>
                                    <option value="2">Hoy</option>
                                    <option value="3">Ayer</option>
                                    <option value="4">Últimos 7 días</option>
                                    <option value="5">Últimos 30 días</option>
                                    <option value="6">Últimos 90 días</option>
                                    <option value="7">Entre las fechas</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row" id="date_container" style="display:none">
                        <div class="col-12 col-md-6">
                            <div class="form-group form-group-default input-group">
                                <div class="form-input-group">
                                    <label>Fecha inicial:</label>
                                    <input name="bqCampo_fecha_x" type="text" class="form-control" placeholder="Seleccione.." id="fecha_inicial">
                                    <input name="bqComparador_fecha_x" type="hidden" value="y" />
                                    <input name="bqTipo_fecha_x" type="hidden" value="date">
                                </div>
                                <div class="input-group-append ">
                                    <span class="input-group-text"><i class="fa fa-calendar"></i></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="form-group form-group-default input-group">
                                <div class="form-input-group">
                                    <label>Fecha final:</label>
                                    <input name="fecha_y" type="text" class="form-control" placeholder="Seleccione.." id="fecha_final">
                                </div>
                                <div class="input-group-append ">
                                    <span class="input-group-text"><i class="fa fa-calendar"></i></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="morefields"></div>
                </form>
            </div>
        </div>
    </div>
    <?= select2() ?>
    <?= fancyTree() ?>
    <?= dateTimePicker() ?>
    <script src="<?= $baseUrl ?>app/modules/back_pqr/formatos/pqr/busqueda.js" data-baseurl="<?= $baseUrl ?>"></script>
</body>

</html>