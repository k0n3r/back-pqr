<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="content-type" content="text/html;charset=UTF-8" />
    <meta charset="utf-8" />
    <title>SGDA</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=10.0, shrink-to-fit=no" />
    <meta name="apple-mobile-web-app-capable" content="yes">
    <?= $linksCss ?>
</head>

<body>
    <div class='container-fluid col-lg-8 pt-2'>
        <!-- Modal -->
        <div class="modal fade" id="modalSearch" tabindex="-1" role="dialog" aria-labelledby="modalSearchLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalSearchLabel">Consultar</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form name='formSearch' id='formSearch' role='form' autocomplete='off'>

                            <div class="form-group form-group-default required">
                                <label>Número radicado:</label>
                                <input class="form-control required" name="numero" type="number">
                            </div>

                            <div class='form-group text-right' id='form_buttons_search'>
                                <button class='btn btn-complete' id='btn-search' type='button'>Consultar</button>
                                <div class='progress-circle-indeterminate d-none' id='spiner'></div>
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
                                        <th>FECHA</th>
                                        <th>DESCRIPCIÓN</th>
                                        <th>INFORMACIÓN</th>
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
            <!-- <img src="../../saia/app/modules/back_pqr/controllers/templates/encabezadoWs.jpeg"> -->
            <?php if ($showLabel) : ?>
                <div class="card-header text-center">
                    <div class="card-title">
                        <h5 class="text-black"><?= $nameForm ?></h5>
                    </div>
                </div>
            <?php endif; ?>
            <div class="card-body">
                <div class="text-right mb-4">
                    <button class="btn btn-default" type="button" data-toggle="modal" data-target="#modalSearch">Consultar <i class="fa fa-search"></i> </button>
                </div>

                <form name='formulario' id='formulario' role='form' autocomplete='off'>
                    <?php if ($showAnonymous) : ?>
                        <div class="form-group" id="group_sys_anonimo">
                            <p>
                                ¿DESEA REGISTRAR ESTA SOLICITUD COMO UNA PERSONA ANÓNIMA?
                                <input type="checkbox" name="sys_anonimo" id="sys_anonimo" value="1" />
                            </p>
                        </div>
                    <?php endif; ?>

                    <?= $contentFields ?>
                    <div class='form-group' id='form_buttons'>
                        <button class='btn btn-complete' id='save_document' type='button'>Continuar</button>
                        <div class='progress-circle-indeterminate d-none' id='spiner'></div>
                    </div>
                </form>
            </div>
        </div>

    </div>
    <?= $scripts ?>
</body>

</html>