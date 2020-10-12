$(function () {
    let params = $('#scriptFinish').data('params');
    $('#scriptFinish').removeAttr('data-params');

    var baseUrl = localStorage.getItem('baseUrl');

    $(document).off('click', '#btn_success').on('click', '#btn_success', function () {

        let observaciones = $("#observaciones").val();
        if (!observaciones) {
            top.notification({
                message: "Por favor ingrese las observaciones",
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
                method: 'finish',
                data: {
                    idft: +params.idft,
                    observaciones: observaciones
                }
            },
            dataType: 'json',
            success: function (response) {
                if (response.success) {
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