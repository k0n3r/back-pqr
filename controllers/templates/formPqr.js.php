
<?php
$code = <<<JAVASCRIPT
$(function () {
    var baseUrl = window.baseUrl;

    window.getCredentials();
    loadjsComponent();

    $("#sys_anonimo").change(function (){
        if($(this).is(':checked')){
            showAnonymousFields();
        }else{
            hideAnonymousFields();
        }
    });

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
                        title: "Radicado No "+response.data.numero,
                        color: 'green',
                        position: "center",
                        overlay: true,
                        timeout: false,
                        icon: 'fa fa-check',
                        layout: 2,
                        message: '<br/>Su solicitud ha sido generada con el número de radicado <strong>'+response.data.numero+'</strong><br/>el seguimiento lo puede realizar en el apartado de consulta con el radicado asignado<br/><br/>Gracias por visitarnos!',
                        onClosed: function (instance, toast, closedBy) {
                            window.location.reload()
                        }
                    });
                } else {
                    console.error(response.message);
                    window.notification({
                        title:'Error!',
                        icon: 'fa fa-exclamation-circle',
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

    //Search
    $("#btn-search").click(function () {
        $("#form_buttons_search").find('button,#spiner').toggleClass('d-none');
        $("#formSearch").trigger('submit');
    });

    $("#formSearch").validate({
        submitHandler: function (form) {
            let dataForm = window.getFormObject($('#formSearch').serializeArray());
            let data=Object.assign(dataForm, {
                formatId: {$formatId}
            });

            $.ajax({
                url: baseUrl + `app/ws/search.php`,
                data
            }).done((response) => {
                $("#result").removeClass('d-none');

                if (response.success) {
                    $("#tbody").empty();

                    if(+response.data.length){
                        $.each(response.data, function (key, value) {
                            let ul=$("<ul>");
                            $.each(value.descripcion, function (k, v) {
                                ul.append(
                                   $("<li>",{
                                       text:v
                                   })
                                )
                            });
                            $("#tbody").append(
                                $('<tr>').append(
                                    $('<td>',{
                                        class: 'text-center',
                                        text: value.fecha
                                    }),
                                    $('<td>').append(ul),
                                    $('<td>',{
                                        class: 'text-center'
                                    }).append(
                                        $("<a>",{
                                            href:value.url,
                                            target: '_blank'
                                        }).text('Ver')
                                    )
                                )
                            );
                        });
                    }else{
                        $("#tbody").append(
                            $('<tr>').append(
                                $('<td>',{
                                    class:'text-center',
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

    function toggleButton(div='form_buttons') {
        $("#"+div).find('button,#spiner').toggleClass('d-none');
    }

    function clearForm(form) {
        form.reset();
        $("select").val("");
        $('select').select2().trigger('change');
    }

    function loadjsComponent() {
        {$content}
    }

    function showAnonymousFields(){
        let fields={$fieldsWithAnonymous};
        $.each(fields, function( i, field ) {
            if(field.required){
                $( "#"+field.name ).rules( "add", { required: true } );
                $( "#group_"+field.name ).addClass("required");
            }else{
                $("#"+field.name).rules( "add", { required: false } );
                $( "#group_"+field.name ).removeClass("required");
            }
            if(field.show){
                $( "#group_"+field.name ).show();
            }else{
                $( "#group_"+field.name ).hide();
            }
        });
    }

    function hideAnonymousFields(){
        let fields={$fieldsWithoutAnonymous};
        $.each(fields, function( i, field ) {
            $( "#group_"+field.name ).show();

            if(field.required){
                $( "#group_"+field.name ).addClass("required");
                $("#"+field.name).rules( "add", { required: true } );
            }else{
                $( "#group_"+field.name ).removeClass("required");
                $("#"+field.name).rules( "add", { required: false } );
            }
        });
    }
});
JAVASCRIPT;

echo $code;