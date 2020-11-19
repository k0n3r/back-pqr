<?php
$max_salida = 10;
$rootPath = $ruta = '';

while ($max_salida > 0) {
    if (is_file($ruta . 'index.php')) {
        $rootPath = $ruta;
        break;
    }

    $ruta .= '../';
    $max_salida--;
}

include_once $rootPath . 'views/assets/librerias.php';
$params = json_encode($_REQUEST);
?>

<div class="row">
    <div class="col-12">
        <form name="formChangeType" id="formChangeType">
            <div class="form-group form-group-default form-group-default-select2 required">
                <label class="my-0">TIPO:</label>
                <select class="form-control full-width required" name="sys_tipo" id="sys_tipo">
                    <option value="">Seleccione ...</option>
                </select>
            </div>
            <div id="divSubType" class="form-group form-group-default form-group-default-select2 required">
                <label class="my-0">CATEGORIA O SUBTIPO:</label>
                <select class="form-control full-width required" name="sys_subtipo" id="sys_subtipo">
                    <option value="">Seleccione ...</option>
                </select>
            </div>
            <div class="form-group form-group-default input-group required date" id="group_sys_fecha_vencimiento">
                <div class="form-input-group">
                    <label for='sys_fecha_vencimiento'>
                        FECHA VENCIMIENTO
                    </label>
                    <input type="text" class="form-control required" id="sys_fecha_vencimiento" name="sys_fecha_vencimiento">
                </div>
                <div class='input-group-append'>
                    <span class='input-group-text'>
                        <i class='fa fa-calendar'></i>
                    </span>
                </div>
            </div>
            <div id="divDependency" class="form-group form-group-default form-group-default-select2 required">
                <label class="my-0">DEPENDENCIA:</label>
                <select class="form-control full-width required" name="sys_dependencia" id="sys_dependencia">
                    <option value="">Seleccione ...</option>
                </select>
            </div>
        </form>
    </div>
</div>
<?= dateTimePicker() ?>
<?= validate() ?>
<script id="scriptEditType" src="../../app/modules/back_pqr/views/js/editType.js" data-params='<?= $params ?>'></script>