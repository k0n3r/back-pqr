$(function () {
    let params = $('#scriptEditType').data('params');
    $('#scriptEditType').removeAttr('data-params');

    var baseUrl = localStorage.getItem('baseUrl');
    var subtypeExist = 0;

    $('#sys_fecha_vencimiento').datetimepicker({
        locale: 'es',
        format: 'YYYY-MM-DD',
        minDate: moment()
    });

    $.ajax({
        type: 'POST',
        url: `${baseUrl}app/modules/back_pqr/app/request.php`,
        data: {
            key: localStorage.getItem('key'),
            token: localStorage.getItem('token'),
            class: 'FtPqrController',
            method: 'getTypes'
        },
        dataType: 'json',
        success: function (response) {
            if (+response.dataType.length) {
                initSelect('sys_tipo', response.dataType);

                $('#sys_tipo').on('change', function (e) {
                    let sys_tipo = this.value;
                    if (!sys_tipo) {
                        $("#sys_fecha_vencimiento").val('');
                        return false;
                    }

                    $.ajax({
                        type: 'POST',
                        dataType: 'json',
                        url: `${baseUrl}app/modules/back_pqr/app/request.php`,
                        data: {
                            key: localStorage.getItem('key'),
                            token: localStorage.getItem('token'),
                            class: 'RequestProcessorController',
                            method: 'getDateForType',
                            data: {
                                type: sys_tipo,
                                idft: +params.idft,
                            }
                        },
                        success: function (response) {
                            if (response.success) {
                                $("#sys_fecha_vencimiento").val(response.date);
                            } else {
                                $("#sys_fecha_vencimiento").val('');
                            }
                        },
                        error: function (...arguments) {
                            console.error(arguments);
                            $("#sys_fecha_vencimiento").val('');
                        }
                    });

                });


            } else {
                top.notification({
                    message: 'No fue posible cargar los tipos',
                    type: 'error'
                });
            }
            if (+response.dataSubType.length) {
                subtypeExist = 1;
                initSelect('sys_subtipo', response.dataSubType);
            } else {
                $("#divSubType").remove();
            }
        }
    });

    function initSelect(id, data) {
        data.forEach(e => {
            $("#" + id).append(
                new Option(e.text, e.id, false, false)
            )
        });

        $("#" + id).select2({
            language: "es",
            placeholder: "Seleccione",
            multiple: false,
            dropdownParent: "#dinamic_modal"
        });
    }

    $(document)
        .off('click', '#btn_success')
        .on('click', '#btn_success', function () {
            $("#formChangeType").trigger('submit');
        });

    $("#formChangeType").validate({
        submitHandler: function (form) {
            let type = $("#sys_tipo").val();
            let expiration = $("#sys_fecha_vencimiento").val();
            let subtype = 0;
            if (subtypeExist) {
                subtype = $("#sys_subtipo").val();
            }
            $.ajax({
                type: 'POST',
                url: `${baseUrl}app/modules/back_pqr/app/request.php`,
                data: {
                    key: localStorage.getItem('key'),
                    token: localStorage.getItem('token'),
                    class: 'FtPqrController',
                    method: 'updateType',
                    data: {
                        idft: +params.idft,
                        expirationDate: expiration,
                        type: type,
                        subtype: subtype
                    }
                },
                dataType: 'json',
                success: function (response) {

                    if (response.success) {
                        top.notification({
                            message: 'Se ha actualizado el tipo',
                            type: 'success'
                        });
                        top.successModalEvent();

                    } else {
                        top.notification({
                            message: response.message,
                            type: 'error'
                        });
                    }
                }
            });
            return false;
        }
    });

});