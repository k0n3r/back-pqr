<?php
$max_salida = 10;
$rootPath = $ruta = "";

while ($max_salida > 0) {
    if (is_file($ruta . "sw.js")) {
        $rootPath = $ruta;
    }

    $ruta .= "../";
    $max_salida--;
}

include_once $rootPath . 'app/vendor/autoload.php';
include_once $rootPath . 'views/assets/librerias.php';

use Saia\controllers\JwtController;
use Saia\controllers\generador\ComponentFormGeneratorController;
use Saia\controllers\AccionController;
use Saia\models\formatos\Formato;
use Saia\Pqr\formatos\pqrsf\FtPqrsf;

//JwtController::check($_REQUEST["token"], $_REQUEST["key"]); 

$Formato = new Formato(28);
$documentId = $_REQUEST['documentId'] ?? 0;
$baseUrl = $Formato->isItem() ? ABSOLUTE_SAIA_ROUTE : $rootPath;

$FtPqrsf = new FtPqrsf;

?>
<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="content-type" content="text/html;charset=UTF-8" />
    <meta charset="utf-8" />
    <title>SGDA</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=10.0, shrink-to-fit=no" />
    <meta name="apple-mobile-web-app-capable" content="yes">

    <?= jquery() ?><?= bootstrap() ?><?= cssTheme() ?>
</head>

<body>
    <div class='container-fluid container-fixed-lg col-lg-8' style="overflow: auto;height:100vh">
        <div class='card card-default'>
            <div class='card-body'>
                <h5 class='text-black w-100 text-center'>
                    FORMULARIO DE PQR
                </h5>
                <form name='formulario_formatos' id='formulario_formatos' role='form' autocomplete='off'>
                    <input type='hidden' name='encabezado' value='1'>
                    <input type='hidden' name='firma' value='1'>
                    <div class='form-group form-group-default required ' id='group_nombre'>
                        <label title='-'>NOMBRE</label>
                        <input class='form-control required' type='text' id='nombre' name='nombre' value='' maxLength='250' />
                    </div>
                    <div class='form-group form-group-default required ' id='group_cuadro_de_texto_obli'>
                        <label title='-'>CUADRO DE TEXTO OBLIGATORIO</label>
                        <input class='form-control required' type='text' id='cuadro_de_texto_obli' name='cuadro_de_texto_obli' value='' maxLength='250' />
                    </div>
                    <div class='form-group form-group-default  ' id='group_cuadro_de_texto_opci'>
                        <label title='-'>CUADRO DE TEXTO OPCIONAL</label>
                        <input class='form-control ' type='text' id='cuadro_de_texto_opci' name='cuadro_de_texto_opci' value='' maxLength='250' />
                    </div>
                    <div class="form-group form-group-default required" id="group_area_de_texto_obliga">
                        <label title="-">
                            AREA DE TEXTO OBLIGATORIO
                        </label>
                        <textarea name="area_de_texto_obliga" id="area_de_texto_obliga" rows="3" class="form-control required"></textarea>

                    </div>
                    <div class="form-group form-group-default " id="group_area_de_texto_opcion">
                        <label title="-">
                            AREA DE TEXTO OPCIONAL
                        </label>
                        <textarea name="area_de_texto_opcion" id="area_de_texto_opcion" rows="3" class="form-control "></textarea>

                    </div>

                    <div class='form-group form-group-default form-group-default-select2 required' id='group_lista_obligatorio'>
                        <label title='-'>LISTA OBLIGATORIO</label>
                        <select class='full-width' name='lista_obligatorio' id='lista_obligatorio' required>
                            <option value=''>Por favor seleccione...</option>
                            <option value='2'>
                                si
                            </option>
                            <option value='1'>
                                no
                            </option>
                        </select>
                        <script>
                            $(document).ready(function() {
                                $('#lista_obligatorio').select2();
                            });
                        </script>
                    </div>

                    <div class='form-group form-group-default form-group-default-select2 ' id='group_lista_opcional'>
                        <label title='-'>LISTA OPCIONAL</label>
                        <select class='full-width' name='lista_opcional' id='lista_opcional'>
                            <option value=''>Por favor seleccione...</option>
                            <option value='2'>
                                si
                            </option>
                            <option value='1'>
                                no
                            </option>
                        </select>
                        <script>
                            $(document).ready(function() {
                                $('#lista_opcional').select2();
                            });
                        </script>
                    </div>

                    <div class='form-group form-group-default required' id='group_radio_obligatorio'>
                        <label title='-'>RADIO OBLIGATORIO</label>
                        <div class='radio radio-success input-group'>
                            <input required type='radio' name='radio_obligatorio' id='radio_obligatorio0' value='73' aria-required='true'>
                            <label for='radio_obligatorio0' class='mr-3'>
                                si
                            </label><input required type='radio' name='radio_obligatorio' id='radio_obligatorio1' value='74' aria-required='true'>
                            <label for='radio_obligatorio1' class='mr-3'>
                                no
                            </label></div>
                        <label id='radio_obligatorio-error' class='error' for='radio_obligatorio' style='display: none;'></label>
                    </div>

                    <div class='form-group form-group-default ' id='group_radio_opcional'>
                        <label title='-'>RADIO OPCIONAL</label>
                        <div class='radio radio-success input-group'>
                            <input type='radio' name='radio_opcional' id='radio_opcional0' value='75' aria-required='true'>
                            <label for='radio_opcional0' class='mr-3'>
                                otra si
                            </label><input type='radio' name='radio_opcional' id='radio_opcional1' value='76' aria-required='true'>
                            <label for='radio_opcional1' class='mr-3'>
                                otra no
                            </label></div>

                    </div>

                    <div class='form-group form-group-default required' id='group_checkbox_obligatorio'>
                        <label title='-'>CHECKBOX OBLIGATORIO</label>
                        <div class='checkbox check-success input-group'>
                            <input required type='checkbox' name='checkbox_obligatorio[]' id='checkbox_obligatorio0' value='77' aria-required='true'>
                            <label for='checkbox_obligatorio0' class='mr-3'>
                                check 1
                            </label><input required type='checkbox' name='checkbox_obligatorio[]' id='checkbox_obligatorio1' value='78' aria-required='true'>
                            <label for='checkbox_obligatorio1' class='mr-3'>
                                check 2
                            </label></div>
                        <label id='checkbox_obligatorio[]-error' class='error' for='checkbox_obligatorio[]' style='display: none;'></label>
                    </div>

                    <div class='form-group form-group-default ' id='group_checkbox_opcional'>
                        <label title='-'>CHECKBOX OPCIONAL</label>
                        <div class='checkbox check-success input-group'>
                            <input type='checkbox' name='checkbox_opcional[]' id='checkbox_opcional0' value='79' aria-required='true'>
                            <label for='checkbox_opcional0' class='mr-3'>
                                check 3
                            </label><input type='checkbox' name='checkbox_opcional[]' id='checkbox_opcional1' value='80' aria-required='true'>
                            <label for='checkbox_opcional1' class='mr-3'>
                                check 4
                            </label></div>

                    </div>
                    <input type='hidden' name='idft_pqrsf' value=''>

                    <?php

                    use Saia\controllers\SessionController;
                    use Saia\core\DatabaseConnection;

                    $selected = $FtPqrsf->dependencia ?? '';
                    $query = DatabaseConnection::getQueryBuilder();
                    $roles = $query
                        ->select("dependencia as nombre, iddependencia_cargo, cargo")
                        ->from("vfuncionario_dc")
                        ->where("estado_dc = 1 and tipo_cargo = 1 and login = :login")
                        ->andWhere(
                            $query->expr()->lte('fecha_inicial', ':initialDate'),
                            $query->expr()->gte('fecha_final', ':finalDate')
                        )->setParameter(":login", SessionController::getLogin())
                        ->setParameter(':initialDate', new DateTime(), \Doctrine\DBAL\Types\Type::DATETIME)
                        ->setParameter(':finalDate', new DateTime(), \Doctrine\DBAL\Types\Type::DATETIME)
                        ->execute()->fetchAll();

                    $total = count($roles);

                    if ($total > 1) {

                        echo "<div class='form-group form-group-default form-group-default-select2 required' id='group_dependencie'>
            <label>PERTENECE A</label>
            <select class='full-width select2-hidden-accessible' name='dependencia' id='dependencia' required>";
                        foreach ($roles as $row) {
                            echo "<option value='{$row["iddependencia_cargo"]}'>
                    {$row["nombre"]} - ({$row["cargo"]})
                </option>";
                        }

                        echo "</select>
                <script>
                $(function (){
                    $('#dependencia').select2();
                    $('#dependencia').val({$selected});
                    $('#dependencia').trigger('change');
                });  
                </script>
            ";
                    } else if ($total == 1) {
                        echo "<div class='form-group form-group-default required' id='group_dependencie'>
                <input class='required' type='hidden' value='{$roles[0]['iddependencia_cargo']}' id='dependencia' name='dependencia'>
                <label>Rol activo</label>
                <div class='form-group'>
                    <label>{$roles[0]["nombre"]} - ({$roles[0]["cargo"]})</label>
                </div>";
                    } else {
                        throw new Exception("Error al buscar la dependencia", 1);
                    }

                    echo "</div>";
                    ?>
                    <input type='hidden' name='anterior' value='<?= $_REQUEST['anterior'] ?>'>
                    <input type='hidden' name='campo_descripcion' value='9219,9204,9205,9206,9207,9208,9209,9210,9211,9212,9213'>
                    <input type='hidden' name='documentId' value='<?= $documentId ?>'>
                    <input type='hidden' id='tipo_radicado' name='tipo_radicado' value='radicacion_entrada'>
                    <input type='hidden' name='formatId' value='28'>
                    <input type='hidden' name='tabla' value='ft_pqrsf'>
                    <input type='hidden' name='formato' value='pqrsf'>
                    <div class='form-group px-0 pt-3' id='form_buttons'><button class='btn btn-complete' id='save_document' type='button'>Continuar</button>
                        <div class='progress-circle-indeterminate d-none' id='spiner'></div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?= jsTheme() ?>
    <?= icons() ?>
    <?= moment() ?>
    <?= select2() ?>
    <?= validate() ?>
    <?= ckeditor() ?>
    <?= jqueryUi() ?>
    <?= fancyTree(true) ?>
    <?= dateTimePicker() ?>
    <?= dropzone() ?>

    <?php

    if ($documentId) {
        $additionalParameters = $FtPqrsf->getRouteParams(FtPqrsf::SCOPE_ROUTE_PARAMS_EDIT);
    } else {
        $additionalParameters = $FtPqrsf->getRouteParams(FtPqrsf::SCOPE_ROUTE_PARAMS_ADD);
    }
    $params = array_merge($_REQUEST, $additionalParameters, ['baseUrl' => $baseUrl]);
    ?>
    <script>
        $(function() {
            $.getScript('<?= $baseUrl ?>app/modules/back_pqr/formatos/pqrsf/funciones.js', () => {
                window.routeParams = <?= json_encode($params) ?>;
                if (+'<?= $documentId ?>') {
                    edit(<?= json_encode($params) ?>)
                } else {
                    add(<?= json_encode($params) ?>)
                }
            });

            $("#add_item").click(function() {
                checkForm((data) => {
                    let options = top.window.modalOptions;
                    options.oldSource = null;
                    top.topModal(options)
                })
            });

            $("#save_item").click(function() {
                checkForm((data) => {
                    top.successModalEvent(data);
                })
            });

            $("#save_document").click(function() {
                checkForm((data) => {
                    let route = "<?= $baseUrl ?>views/documento/index_acordeon.php?";
                    route += $.param(data);
                    window.location.href = route;
                })
            });

            function checkForm(callback) {
                $("#formulario_formatos").validate({
                    ignore: [],
                    errorPlacement: function(error, element) {
                        let node = element[0];

                        if (
                            node.tagName == 'SELECT' &&
                            node.className.indexOf('select2') !== false
                        ) {
                            error.addClass('pl-3');
                            element.next().append(error);
                        } else {
                            error.insertAfter(element);
                        }
                    },
                    submitHandler: function(form) {
                        $("#form_buttons").find('button,#spiner').toggleClass('d-none');

                        executeEvents(callback);
                    },
                    invalidHandler: function() {
                        $("#save_document").show();
                        $("#boton_enviando").remove();
                    }
                });
                $("#formulario_formatos").trigger('submit');
            }

            function executeEvents(callback) {
                let documentId = $("[name='documentId']").val();

                (+documentId ? beforeSendEdit() : beforeSendAdd())
                .then(r => {
                    sendData()
                        .then(requestResponse => {
                            (+documentId ? afterSendEdit(requestResponse) : afterSendAdd(requestResponse))
                            .then(r => {
                                    callback(requestResponse.data);
                                })
                                .catch(message => {
                                    fail(message);
                                })
                        })
                }).catch(message => {
                    fail(message);
                });
            }

            function sendData() {
                return new Promise((resolve, reject) => {
                    let data = $('#formulario_formatos').serialize() + '&' +
                        $.param({
                            key: localStorage.getItem('key'),
                            token: localStorage.getItem('token')
                        });

                    $.post(
                        '<?= $baseUrl ?>app/documento/guardar_ft.php',
                        data,
                        function(response) {
                            if (response.success) {
                                resolve(response)
                            } else {
                                reject(response);
                            }
                        },
                        'json'
                    );
                });
            }

            function fail(message) {
                $("#form_buttons").find('button,#spiner').toggleClass('d-none');
                top.notification({
                    message: message,
                    type: 'error',
                    title: 'Error!'
                });
            }
        });
    </script>
    <?= AccionController::execute(
        AccionController::ACTION_ADD,
        AccionController::BEFORE_MOMENT,
        $FtPqrsf ?? null,
        $Formato
    ) ?>
</body>

</html>