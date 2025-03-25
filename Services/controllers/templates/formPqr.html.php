<!DOCTYPE html>
<html lang="es">

<head>
    <meta http-equiv="content-type" content="text/html;charset=UTF-8"/>
    <meta charset="utf-8"/>
    <title>SAIA - SGDEA</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=10.0, shrink-to-fit=no"/>
    <meta name="apple-mobile-web-app-capable" content="yes">
    <?= $linksCss ?>
</head>

<body>
<div class='container-fluid col-lg-8 pt-2'>
    <!-- Modal -->
    <div class="modal fade" id="modalSearch" tabindex="-1" role="dialog" aria-labelledby="modalSearchLabel"
         aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <form name='formSearch' id='formSearch' role='form' autocomplete='off'>
                        <input type="hidden" name="_csrf" value="">
                        <div class="form-group form-group-default required">
                            <label>Número de Consecutivo:
                                <input class="form-control required" name="numero" type="number">
                            </label>
                        </div>

                        <h6 style="color:#ff0000;font-size: 12px">Si al momento de crear la solicitud suministro
                            el "<?= $emailLabel ?>", debe ingresarlo si desea obtener resultados
                        </h6>
                        <div class="form-group form-group-default">
                            <label><?= $emailLabel ?>:
                                <input class="form-control" name="sys_email" type="email">
                            </label>
                        </div>

                        <div class='form-group text-right' id='form_buttons_search'>
                            <button class='btn btn-complete' id='btn-search' type='button'>Consultar</button>
                            <div class='progress-circle-indeterminate d-none' id='spinerSearch'></div>
                        </div>

                    </form>


                    <div id="result" class="card d-none">
                        <div class="card-header text-center">
                            <div class="card-title">
                                <h5 class="text-black">RESULTADOS</h5>
                            </div>
                        </div>
                        <div class="card-body">
                            <table class="table">
                                <thead class="thead-light text-center" id="thead">
                                <tr>
                                    <th>FECHA</th>
                                    <th>DESCRIPCIÓN</th>
                                    <th>INFORMACIÓN</th>
                                </tr>
                                </thead>
                                <tbody id="tbody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <!-- <img src="img/encabezadoWs.jpeg"> -->
        <?php if ($showLabel) : ?>
            <div class="card-header text-center">
                <div class="card-title">
                    <h5 class="text-black"><?= $nameForm ?></h5>
                </div>
            </div>
        <?php endif; ?>
        <div class="card-body">
            <div class="text-right mb-4">
                <button type="button" class="btn btn-default" data-bs-toggle="modal" data-bs-target="#modalSearch">
                    Consultar<i class="fa fa-search"></i>
                </button>
            </div>

            <form name='formulario' id='formulario' role='form' autocomplete='off'>
                <input type="hidden" name="_csrf" value="">
                <input type="hidden" name="geolocalizacion" id="geolocalizacion" value="">

                <?php if ($showAnonymous) : ?>
                    <div class="form-group" id="group_sys_anonimo">
                        <label for="sys_anonimo">
                            ¿DESEA REGISTRAR ESTA SOLICITUD COMO UNA PERSONA ANÓNIMA?
                            <input type="checkbox" name="sys_anonimo" id="sys_anonimo" value="1"/>
                        </label>
                    </div>
                <?php endif; ?>

                <?= $fields ?>
                <div class='form-group' id='form_buttons'>
                    <button class='btn btn-complete' id='save_document' type='button'>Continuar</button>
                    <div class='progress-circle-indeterminate d-none' id='spiner'></div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($recaptchaPublicKey) : ?>
    <script src="https://www.google.com/recaptcha/api.js?render=<?= $recaptchaPublicKey ?>"></script>
<?php endif; ?>

<?= $scripts ?>
</body>

</html>