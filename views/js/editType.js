$(function () {
    let params = $('#scriptEditType').data('params');
    $('#scriptEditType').removeAttr('data-params');

    var baseUrl = localStorage.getItem('baseUrl');
    var subtypeExist = 0;
    var dependencyExist = 0;

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
            method: 'getDataForEditTypes'
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


            if (+response.activeDependency) {
                dependencyExist = 1;
                initSelectDependency();
            } else {
                $("#divDependency").remove();
            }

            getValues()
        }
    });

    function initSelectDependency() {
        let options = {
            language: "es",
            placeholder: "Ingrese el nombre",
            multiple: false,
            ajax: {
                delay: 400,
                url: `${baseUrl}app/modules/back_pqr/app/request.php`,
                dataType: "json",
                data: function (p) {
                    var query = {
                        key: localStorage.getItem("key"),
                        token: localStorage.getItem("token"),
                        class: "RequestProcessorController",
                        method: "getListForField",
                        data: {
                            name: 'sys_dependencia',
                            term: p.term
                        }
                    };
                    return query;
                }
            }
        };
        $('#sys_dependencia').select2(options);
    }

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

    function getValues() {
        $.ajax({
            type: 'POST',
            dataType: 'json',
            url: `${baseUrl}app/modules/back_pqr/app/request.php`,
            data: {
                key: localStorage.getItem('key'),
                token: localStorage.getItem('token'),
                class: 'FtPqrController',
                method: 'getValuesForType',
                data: {
                    idft: +params.idft
                }
            },
            success: function (response) {
                if (response.success) {
                    if (+response.data.sys_tipo) {
                        $('#sys_tipo').val(response.data.sys_tipo).trigger('change');
                    }
                    if (+response.data.sys_subtipo) {
                        $('#sys_subtipo').val(response.data.sys_subtipo).trigger('change');
                    }
                    $("#sys_fecha_vencimiento").val(response.data.sys_fecha_vencimiento);

                    if (response.data.sys_dependencia) {
                        let u = response.data.optionsDependency;
                        var option = new Option(u.text, u.id, true, true);
                        $('#sys_dependencia')
                            .append(option)
                            .trigger('change');
                    }

                } else {
                    console.error(response)
                    top.notification({
                        message: 'No fue posible cargar los valores seleccionados',
                        type: 'error'
                    });
                }
            },
            error: function (...arguments) {
                console.error(arguments);
            }
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
            let dependency = 0;

            if (subtypeExist) {
                subtype = $("#sys_subtipo").val();
            }

            if (dependencyExist) {
                dependency = $("#sys_dependencia").val();
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
                        subtype: subtype,
                        dependency: dependency
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