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

$_REQUEST['baseUrl'] = $rootPath;
$params = json_encode($_REQUEST);
?>

<div class="row">
    <div class="col-12">
        <div class='form-group form-group-default'>
            <label>RESPONDER CON DOCUMENTO ?</label>
            <div class='radio radio-success input-group'>
                <input type='radio' name='type' value='1' id="existente" checked>
                <label for='existente' class='mr-3'>
                    EXISTENTE
                </label>

                <input type='radio' name='type' value='2' id="nuevo">
                <label for='nuevo' class='mr-3'>
                    NUEVO
                </label>
            </div>
        </div>
    </div>
</div>


<div class="row" id="divSelect">
    <div class="col-12">
        <div class="form-group form-group-default form-group-default-select2">
            <label class="my-0">BUSCAR DOCUMENTO :</label>
            <select class="form-control" id="iddocumento"></select>
        </div>
    </div>
</div>
<?= select2() ?>
<script id="scriptResponder" src="../../app/modules/back_pqr/views/js/responder.js" data-params='<?= $params ?>'></script>