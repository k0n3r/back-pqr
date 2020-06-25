
<?php
$code = <<<JAVASCRIPT
$(function () {
    var baseUrl = window.baseUrl;

    window.getCredentials();
    loadjsComponent();

    $("#save_document").click(function () {
        $("#form_buttons").find('button,#spiner').toggleClass('d-none');
        $("#formulario").trigger('submit');
    });

    $("#formulario").validate({
        errorPlacement: function (error, element) {
            let node = element[0];

            if (
                node.tagName == "SELECT" &&
                node.className.indexOf("select2") !== false
            ) {
                error.addClass("pl-2");
                element.next().append(error);
            } else {
                error.insertAfter(element);
            }
        },
        submitHandler: function (form) {
            let dataForm = window.getFormObject($('#formulario').serializeArray());
            let data=Object.assign(dataForm, {
                formatId: {$formatId},
                dependencia: localStorage.getItem('WsRol') 
            });

            $.ajax({
                url: baseUrl + `app/ws/save.php`,
                data,
            }).done((response) => {
                if (response.success) {
                    clearForm(form);
                    window.notification({
                        color: 'green',
                        timeout: 30000,
                        message: 'Se solicitud ha sido generada con el numero de radicado ' + response.data.numero
                    });
                    setTimeout(function () { window.location.reload() }, 3000);
                } else {
                    console.error(response.message);
                    window.notification({
                        color: 'red',
                        message: +response.code == 200 ? response.message : 'No fue posible radicar su solicitud'
                    });
                }
            }).fail(function () {
                console.error(...arguments)
            }).always(function () {
                toggleButton();
            });

            return false;
        },
        invalidHandler: function () {
            toggleButton();
        }
    });

    function toggleButton() {
        $("#form_buttons").find('button,#spiner').toggleClass('d-none');
    }

    function clearForm(form) {
        form.reset();
        $("select").val("");
        $('select').select2().trigger('change');
    }

    function loadjsComponent() {
        {$content}
    }
});
JAVASCRIPT;

echo $code;
