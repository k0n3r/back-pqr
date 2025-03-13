<?php
$comment = $recaptchaPublicKey ? '' : '//';

$code = <<<JAVASCRIPT
$(function () {
    const key = window.credential.key; //Componente dropzone   
    const token = window.credential.token; //Componente dropzone   
    const isRequiredGeolocation=$isRequiredGeolocation; 
    const fieldsWithAnonymous = $fieldsWithAnonymous;
    const fieldsWithoutAnonymous = $fieldsWithoutAnonymous;
    
    function clearForm(form) {
        form.reset();
        $("select").val("");
        $('select').select2().trigger('change');
    }

    function loadjsComponent() {
        $componentsJS
    }
    
    function loadGeolocation() {
        return new Promise((resolve, reject) => {
            if (!navigator.geolocation) {
                console.info("Tu navegador no soporta geolocalización");
                if (isRequiredGeolocation) {
                    top.notification({
                        message: 'Tu navegador no soporta geolocalización y es requerida.',
                        type: 'error',
                        title: 'Error!'
                    });
                    reject();
                }
                resolve();
            }
    
            if ($("#geolocalizacion").val().trim().length) {
                resolve();
            }
    
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const geolocation = {
                        latitude: position.coords.latitude,
                        longitude: position.coords.longitude
                    };
                    $("#geolocalizacion").val(JSON.stringify(geolocation));
                    resolve();
                },
                (error) => {
                    console.error("Error al obtener la ubicación:", error.message);
    
                    if (isRequiredGeolocation) {
                        let errorMessage = 'No se pudo obtener la ubicación.';
                        switch (error.code) {
                            case error.PERMISSION_DENIED:
                                errorMessage += ' El usuario denegó el acceso a la geolocalización.';
                                break;
                            case error.POSITION_UNAVAILABLE:
                                errorMessage += ' La información de la ubicación no está disponible.';
                                break;
                            case error.TIMEOUT:
                                errorMessage += ' El tiempo de espera para obtener la ubicación expiró.';
                                break;
                            default:
                                errorMessage += ' Error desconocido.';
                                break;
                        }
    
                        top.notification({
                            message: errorMessage + ' Verifica los permisos.',
                            type: 'error',
                            title: 'Error!'
                        });
                        
                        reject();
                    }
                    resolve(); 
                    
                }
            );
        });
    }
    
    function loadHelper() {
        const plataforma = localStorage.getItem('breakpoint');
        const accion = (plataforma === 'xs' || plataforma === 'sm') ? 'hover' : 'click';
        
        $('[data-bs-toggle="popover"]').each((index, element) => {
            const el = $(element);

            el.popover({
                html: true,
                trigger: accion,
                placement: 'right',
                content: function () {
                    const content = $("#" + el.data('field') + "_help").clone().removeClass('d-none');
                    return content[0].outerHTML;
                }
            });
        });
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
                node.tagName === "SELECT" &&
                node.className.indexOf("select2") !== false
            ) {
                error.addClass("ps-2");
                element.next().append(error);
            } else {
                error.insertAfter(element);
            }
        },
        submitHandler: function (form) {
$comment    grecaptcha.ready(function () {
$comment       grecaptcha.execute('$recaptchaPublicKey', { action: 'submit' }).then(function (tokenRecaptcha) {

                    let dataForm = window.getFormObject($('#formulario').serializeArray());
                    let data = Object.assign(dataForm, {
$comment                tokenRecaptcha,
                        formatId: $formatId,
                        webservice : 1,
                        dependencia: window.credential.WsRol
                    });

                        loadGeolocation().then(() => {
                        
                            $.ajax({
                                method: 'POST',
                                url: '$urlSaveFt',
                                data,
                            }).done((response) => {
                                if (response.success) {
                                    clearForm(form);
                                    window.notification({
                                        title: "Número Consecutivo: " + response.data.number,
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
                                window.toggleButton('form_buttons');
                            });
                        
                        }).catch(() => {
                            setTimeout(() => {
                                window.location.href = 'https://www.saiasoftware.com';
                            }, 4000);
                        });
$comment      });
$comment   });

            return false;
        },
        invalidHandler: function () {
            window.toggleButton('form_buttons');
        }
    });

    //Search
    $("#btn-search").click(function () {
        $("#form_buttons_search").find('button,#spinerSearch').toggleClass('d-none');
        $("#formSearch").trigger('submit');
    });

    $("#formSearch").validate({
        submitHandler: function (form) {
            let data = window.getFormObject($('#formSearch').serializeArray());

            $.ajax({
                url: `/api/pqr/searchByNumber`,
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
                window.toggleButton('form_buttons_search');
            });

            return false;
        },
        invalidHandler: function () {
            window.toggleButton('form_buttons_search');
        }
    });

    //TERMINA Search

    loadGeolocation().then(() => {
        loadjsComponent();
        loadHelper();
    }).catch(() => {
        setTimeout(() => {
           window.location.href = 'https://www.saiasoftware.com';
        }, 4000);
    });

});
JAVASCRIPT;
echo $code;