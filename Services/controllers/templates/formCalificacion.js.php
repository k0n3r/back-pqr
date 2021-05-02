
<?php
$code = <<<JAVASCRIPT
    $(function () {
        var baseUrl = window.baseUrl;

        (function init(){
            loadjsComponent();
            validParameters();
        })();

        $("#save_document").click(function () {
            $("#form_buttons").find('button,#spiner').toggleClass('d-none');
            $("#formulario").trigger('submit');
        });

        $("#formulario").validate({
            ignore: [],
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
                grecaptcha.execute('$recaptchaPublicKey', { action: 'submit' }).then(function (tokenRecaptcha) {

                    let dataForm = window.getFormObject($('#formulario').serializeArray());
                    let data = Object.assign(dataForm, {
                        formatId: $formatId,
                        dependencia:window.credential.WsRol,
                        tokenRecaptcha: tokenRecaptcha,
                        ft_pqr_respuesta: localStorage.getItem('WsFtPqr'),
                        anterior: localStorage.getItem('WsIddocPqr')
                    });

                    $.ajax({
                        url: baseUrl + '$urlSaveFt',
                        data,
                    }).done((response) => {
                        if (response.success) {
                            clearForm(form);
        
                            window.notification({
                                title: "¡Muchas gracias por su tiempo!",
                                color: 'green',
                                position: "center",
                                overlay: true,
                                timeout: false,
                                icon: 'fa fa-check',
                                layout: 2,
                                message: '<br/>Con esta calificación nos ayuda a mejorar nuestros servicios',
                                onClosed: function (instance, toast, closedBy) {
                                    window.location.href = '../pqr/index.html';
                                }
                            });
                        } else {
                            console.error(response.message);
                            window.notification({
                                title:'Error!',
                                icon: 'fa fa-exclamation-circle',
                                color: 'red',
                                message: +response.code == 200 ? response.message : 'No fue posible calificar la solicitud'
                            });
                        }
                    }).fail(function () {
                        console.error(...arguments)
                    }).always(function () {
                        toggleButton();
                    });
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
            if (typeof select2 === 'function') {
                $("select").val("");
                $('select').select2().trigger('change');
            }
        }

        function loadjsComponent() {
            $content
        }

        function validParameters(){
            let d = window.getVariableFromUrl('d');
            if (!d) {
                window.notification({
                    title:'Error!',
                    icon: 'fa fa-exclamation-circle',
                    timeout: 5000,
                    color: 'red',
                    message: 'Por favor ingrese nuevamente desde la URL que se le envio al e-mail registrado'
                });
                setTimeout(function () { window.location.href="404.html" }, 5000);
                return;
            }
            decrypt(d);
        }

        function decrypt(d){
            $.ajax({
                method:'get',
                url: baseUrl + `api/pqr/decrypt`,
                async: false,
                data:{
                    dataCrypt: d
                }
            }).done((response) => {
                localStorage.setItem('WsFtPqr', response.data.ft_pqr_respuesta);
                localStorage.setItem('WsIddocPqr', response.data.anterior);
            }).fail(function () {
                console.error(...arguments)
            });
        }
    });
JAVASCRIPT;

echo $code;
