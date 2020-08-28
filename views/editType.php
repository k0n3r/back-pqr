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
        <form>
            <div class="form-group form-group-default form-group-default-select2 required">
                <label class="my-0">TIPO DE PQRSF :</label>
                <select class="form-control full-width required" id="sys_tipo">
                    <option value="">Seleccione ...</option>
                </select>
            </div>
        </form>
    </div>
</div>
<?= select2() ?>
<script id="scriptEditType" src="../../app/modules/back_pqr/views/js/editType.js" data-params='<?= $params ?>'></script>