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
    <div class="container-fluid h-100" style="overflow-y: auto">

        <div class="card">
            <div class="card-header text-center">
                <div class="card-title">
                    <h5 class="text-black"><?= $nameForm ?></h5>
                </div>
            </div>
            <div class="modal-body">
                <form name='formulario' id='formulario' role='form' autocomplete='off'>
                    <?= $fields ?>
                    <div class="form-group">
                        <button type="button" class="btn btn-success"></button>
                    </div>
                </form>
            </div>
        </div>

    </div>
    <?= $scripts ?>
</body>

</html>