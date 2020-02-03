<?php
$max_salida = 10;
$rootPath = $ruta = '';

while ($max_salida > 0) {
    if (is_file($ruta . 'sw.js')) {
        $rootPath = $ruta;
        break;
    }

    $ruta .= '../';
    $max_salida--;
}

include_once $rootPath . 'app/vendor/autoload.php';

use Saia\controllers\JwtController;
use Saia\Pqr\formatos\pqrsf\FtPqrsf;

try {
    JwtController::check($_REQUEST["token"], $_REQUEST["key"]); 
    
    $documentId = $_REQUEST["documentId"];
    $FtPqrsf = FtPqrsf::findByDocumentId($documentId);
    $Documento = $FtPqrsf->Documento;
    $Documento->addRead($documentId, $_REQUEST["key"]);
    $Formato = $Documento->getFormat();

    if(
        !$_REQUEST['mostrar_pdf'] && !$_REQUEST['actualizar_pdf'] && (
            ($_REQUEST["tipo"] && $_REQUEST["tipo"] == 5) ||
            0 == 0
        )
    ): ?>
        <!DOCTYPE html>
        <html>
            <head>
                <meta http-equiv="content-type" content="text/html;charset=UTF-8" />
                <meta charset="utf-8" />
                <meta name="viewport"
                    content="width=device-width, initial-scale=1.0, maximum-scale=10.0, shrink-to-fit=no" />
                <meta name="apple-mobile-web-app-capable" content="yes">
                <meta name="apple-touch-fullscreen" content="yes">
                <meta name="apple-mobile-web-app-status-bar-style" content="default">
                <meta content="" name="description" />
                <meta content="" name="Cero K" /> 
            </head>
            <body>
                <div class="container bg-master-lightest mx-0 px-2 px-md-2 mw-100">
                    <div id="documento" class="row p-0 m-0">
                        <div id="pag-0" class="col-12 page_border bg-white">
                            <div class="page_margin_top mb-0" id="doc_header">
                            <?php include_once $rootPath . "views/formatos/librerias/header_nuevo.php" ?>
                            </div>
                            <div id="pag_content-0" class="page_content">
                                <div id="page_overflow">
                                    
                                </div>
                            </div>
                            <?php include_once $rootPath . "views/formatos/librerias/footer_nuevo.php" ?>
                        </div> <!-- end page-n -->
                    </div> <!-- end #documento-->
                </div> <!-- end .container -->
            </body>
            <?php
                $additionalParameters=$FtPqrsf->getRouteParams(FtPqrsf::SCOPE_ROUTE_PARAMS_SHOW);
                $params=array_merge($_REQUEST,$additionalParameters);
            ?>
            <script>
                $(function(){
                    $.getScript('<?= ABSOLUTE_SAIA_ROUTE ?>app/modules/back_pqr/formatos/pqrsf/funciones.js', () => {
                        window.routeParams=<?= json_encode($params) ?>;
                        show(<?= json_encode($params) ?>)
                    });
                });
            </script>
        </html>
    <?php else:
        $params = [
            "type" => "TIPO_DOCUMENTO",
            "typeId" => $documentId,
            "exportar" => $Formato->exportar,
            "ruta" => base64_encode($Documento->pdf)
        ];

        if(
            $_REQUEST["actualizar_pdf"] ||
            (
                !$Documento->pdf && (
                    $Formato->mostrar_pdf == 1 ||
                    $_REQUEST['mostrar_pdf']
                )
            )
        ){
            $params["actualizar_pdf"] = 1;
        }

        $url = ABSOLUTE_SAIA_ROUTE . "views/visor/pdfjs/viewer.php?";
        $url.= http_build_query($params);

        echo "<iframe width='100%' frameborder='0' onload='this.height = window.innerHeight - 20' src='{$url}'></iframe>";
    endif; 
} catch (\Throwable $th) {
    die($th->getMessage());
}