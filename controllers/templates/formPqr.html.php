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

        <div class="card">
            <div class="card-header text-center">
                <div class="card-title">
                    <h5 class="text-black"><?= $nameForm ?></h5>
                </div>
            </div>
            <div class="card-body">
                <div class="text-right mb-4"><?= $hrefSearch ?></div>
                <form name='formulario' id='formulario' role='form' autocomplete='off'>
                    <?= $fields ?>
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