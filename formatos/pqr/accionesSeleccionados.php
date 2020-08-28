<?php
$max_salida = 6;
$rootPath = $ruta = "";
while ($max_salida > 0) {
    if (is_file($ruta . "index.php")) {
        $rootPath = $ruta;
    }
    $ruta .= "../";
    $max_salida--;
}

include_once $rootPath . "views/assets/librerias.php";
echo select2();

function answers(array $data)
{
    $code = <<<HTML
    <select id='actions' class='pull-left btn btn-lg'>
        <option value=''>Acciones...</option>
        <option value='1'>Responder</option>
    </select>
    <script>$("#actions").select2()</script>
HTML;

    return $code;
}
