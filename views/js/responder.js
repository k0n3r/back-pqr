$(function () {
    let params = $('#scriptResponder').data('params');
    $('#scriptResponder').removeAttr('data-params');

    $("#iddocumento").select2({
        language: "es",
        multiple: false,
        minimumInputLength: 1,
        dropdownParent: "#dinamic_modal",
        ajax: {
            delay: 400,
            url: `${params.baseUrl}app/modules/back_pqr/app/request.php`,
            dataType: 'json',
            data: function (params) {
                var query = {
                    key: localStorage.getItem('key'),
                    token: localStorage.getItem('token'),
                    class: 'SearchDocumentController',
                    method: 'search',
                    data: {
                        radicado: params.term
                    }
                };
                return query;
            },
            processResults: function (response) {
                return {
                    results: response.data,
                };
            }
        }
    });

    $("[name='type']").change(function () {
        if ($(this).val() == 1) {
            $("#divSelect").show();
        } else {
            $("#divSelect").hide();
        }
    });

    $("#btn_success").click(function () {
        let type = $("[name='type']:checked").val();
        if (+type == 1) {
            let iddocumento = $("#iddocumento").val();
            if (+iddocumento) {
                $.ajax({
                    type: 'POST',
                    url: `${params.baseUrl}app/modules/back_pqr/app/request.php`,
                    data: {
                        key: localStorage.getItem('key'),
                        token: localStorage.getItem('token'),
                        class: 'PqrAnswerController',
                        method: 'store',
                        data: {
                            fk_pqr: +params.documentId,
                            fk_respuesta: +iddocumento
                        }
                    },
                    dataType: 'json',
                    success: function (response) {

                        if (response.success) {
                            top.notification({
                                message: response.message,
                                type: 'success'
                            });
                            $('#table').bootstrapTable('refresh');
                        } else {
                            top.notification({
                                message: response.message,
                                type: 'error'
                            });
                        }
                    }
                });
            } else {
                top.notification({
                    type: "error",
                    message: "Por favor seleccione el documento"
                });
            }
        } else {
            $.post(
                `${params.baseUrl}app/formato/consulta_rutas.php`,
                {
                    key: localStorage.getItem('key'),
                    token: localStorage.getItem('token'),
                    formatName: "pqr_respuesta",
                    fk_pqr: +params.documentId
                },
                function (response) {
                    if (response.success) {
                        let route = params.baseUrl + response.data.ruta_adicionar;
                        $('#iframe_workspace').attr('src', route);
                        top.closeTopModal();
                    } else {
                        top.notification({
                            type: 'error',
                            message: response.message
                        });
                    }
                },
                'json'
            );
        }
    });

});