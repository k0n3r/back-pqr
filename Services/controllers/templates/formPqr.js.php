<?php
$code = <<<JAVASCRIPT
$(function () {
    var baseUrl = window.baseUrl;
    var key = window.credential.key; //Componente dropzone   
    var token = window.credential.token; //Componente dropzone    

    loadjsComponent();

    const fieldsWithAnonymous = $fieldsWithAnonymous;
    const fieldsWithoutAnonymous = $fieldsWithoutAnonymous;
    $("#sys_anonimo").change(function () {
        if ($(this).is(':checked')) {
            $.each(fieldsWithAnonymous, function (i, field) {
                processField(field);
                const sGroup = $("#group_" + field.name);
                if (field.show) {
                    sGroup.show();
                } else {
                    sGroup.hide();
                }
            });

        } else {
            $.each(fieldsWithoutAnonymous, function (i, field) {
                processField(field, true);
            });
        }
    });


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
            grecaptcha.ready(function () {
                grecaptcha.execute('$recaptchaPublicKey', { action: 'submit' }).then(function (tokenRecaptcha) {

                    let dataForm = window.getFormObject($('#formulario').serializeArray());
                    let data = Object.assign(dataForm, {
                        formatId: $formatId,
                        dependencia: window.credential.WsRol,
                        tokenRecaptcha
                    });

                    $.ajax({
                        method: 'POST',
                        url: baseUrl + '$urlSaveFt',
                        data,
                    }).done((response) => {
                        if (response.success) {
                            clearForm(form);
                            window.notification({
                                title: "Radicado No " + response.data.number,
                                color: 'green',
                                position: "center",
                                overlay: true,
                                timeout: false,
                                icon: 'fa fa-check',
                                layout: 2,
                                message: response.data.messageBody,
                                onClosed: function (instance, toast, closedBy) {
                                    window.location.reload()
                                }
                            });

                        } else {
                            console.error(response);
                            window.notification({
                                title: 'Error!',
                                icon: 'fa fa-exclamation-circle',
                                color: 'red',
                                message: 'No fue posible radicar su solicitud'
                            });
                        }
                    }).fail(function () {
                        console.error(...arguments)
                    }).always(function () {
                        toggleButton();
                    });


                });
            });

            return false;
        },
        invalidHandler: function () {
            toggleButton();
        }
    });

    //Search
    $("#btn-search").click(function () {
        $("#form_buttons_search").find('button,#spiner').toggleClass('d-none');
        $("#formSearch").trigger('submit');
    });

    $("#formSearch").validate({
        submitHandler: function (form) {
            let data = window.getFormObject($('#formSearch').serializeArray());

            $.ajax({
                method: "GET",
                url: baseUrl + `api/pqr/searchByNumber`,
                data
            }).done((response) => {
                $("#result").removeClass('d-none');

                if (response.success) {
                    $("#tbody").empty();

                    if (+response.data.length) {
                        $.each(response.data, function (key, value) {
                            let ul = $("<ul>");
                            $.each(value.descripcion, function (k, v) {
                                ul.append(
                                    $("<li>", {
                                        text: v
                                    })
                                )
                            });
                            $("#tbody").append(
                                $('<tr>').append(
                                    $('<td>', {
                                        class: 'text-center',
                                        text: value.fecha
                                    }),
                                    $('<td>').append(ul),
                                    $('<td>', {
                                        class: 'text-center'
                                    }).append(
                                        $("<a>", {
                                            href: value.url,
                                            target: '_blank'
                                        }).text('Ver')
                                    )
                                )
                            );
                        });
                    } else {
                        $("#tbody").append(
                            $('<tr>').append(
                                $('<td>', {
                                    class: 'text-center',
                                    colspan: 3,
                                    text: 'NO SE ENCONTRARON RESULTADOS'
                                })
                            )
                        );
                    }

                }

            }).fail(function () {
                console.error(...arguments)
            }).always(function () {
                toggleButton('form_buttons_search');
            });

            return false;
        },
        invalidHandler: function () {
            toggleButton('form_buttons_search');
        }
    });

    //TERMINA Search

    function toggleButton(div = 'form_buttons') {
        $("#" + div).find('button,#spiner').toggleClass('d-none');
    }

    function clearForm(form) {
        form.reset();
        $("select").val("");
        $('select').select2().trigger('change');
    }

    function loadjsComponent() {
        $content
    }

    function processField(field, applyShow = false) {
        const sGroup = $("#group_" + field.name);
        if (applyShow) {
            sGroup.show();
        }

        let selector;
        if (field.type === "Radio" || field.type === "Checkbox") {
            selector = $("[name^='" + field.name + "']");
        } else {
            selector = $("#" + field.name);
        }

        if (field.required) {
            selector.rules("add", { required: true });
            sGroup.addClass("required");
        } else {
            selector.rules("add", { required: false });
            sGroup.removeClass("required");
        }
    }
});
JAVASCRIPT;
echo $code;