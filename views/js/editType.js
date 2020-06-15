$(function () {
    let params = $('#scriptEditType').data('params');
    $('#scriptEditType').removeAttr('data-params');

    var baseUrl = localStorage.getItem('baseUrl');

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
            if (+response.data.length) {
                response.data.forEach(e => {
                    $("#sys_tipo").append(
                        new Option(e.text, e.id, false, false)
                    )
                });

            } else {
                top.notification({
                    message: 'No fue posible cargar los tipos',
                    type: 'error'
                });
            }
        }
    });

    $("#sys_tipo").select2({
        language: "es",
        placeholder: "Seleccione el nuevo tipo",
        multiple: false,
        dropdownParent: "#dinamic_modal"
    });

    $(document).off('click', '#btn_success').on('click', '#btn_success', function () {

        let type = $("#sys_tipo").val();
        if (!+type) {
            top.notification({
                message: "Por favor seleccione el nuevo tipo",
                type: 'error'
            });
            return false;
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
                    type: type
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
    });

});