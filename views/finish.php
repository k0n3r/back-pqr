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
            <div class="form-group form-group-default required" id="group_observaciones">
                <label title="Observaciones">OBSERVACIONES: </label>
                <textarea name="observaciones" id="observaciones" rows="3" class="form-control required"></textarea>
            </div>
        </form>
    </div>
</div>
<?= select2() ?>
<script id="scriptFinish" src="../../app/modules/back_pqr/views/js/finish.js" data-params='<?= $params ?>'></script>