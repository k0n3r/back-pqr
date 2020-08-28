<?php
$max_salida = 10;
$rootPath = $ruta = '';

while ($max_salida > 0) {
    if (is_file($ruta . 'index.php')) {
        $rootPath = $ruta;
    }

    $ruta .= '../';
    $max_salida--;
}

include_once $rootPath . 'views/assets/librerias.php';
$params = json_encode($_REQUEST);
?>

<div class="row">
    <div class="col-12">
        <table id="tableHistory"></table>
    </div>
</div>

<?= bootstrapTable() ?>
<script id="scriptHistory" src="../../app/modules/back_pqr/views/js/viewHistory.js" data-params='<?= $params ?>'></script>