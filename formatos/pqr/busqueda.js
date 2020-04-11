$(function () {
    let baseUrl = $('script[data-baseurl]').data('baseurl');
    (function init() {

        $('#filtro_fecha').select2();
        createPicker();

        $.get(`${baseUrl}app/modules/back_pqr/formatos/pqr/buscar.php`, function (html) {
            let res = html.replace(/d\./g, "v.");
            $("#morefields").empty().append(res);
        });
    })();


    $('#clear').on('click', function () {
        $('#filtro_fecha')
            .val(1)
            .trigger('change');

        $('#fecha_inicial')
            .data('DateTimePicker')
            .clear();
        $('#fecha_final')
            .data('DateTimePicker')
            .clear();
    });

    $('#btn_success').on('click', function () {
        $.post(`${baseUrl}app/busquedas/procesa_filtro_busqueda.php`,
            $("#find_document_form").serialize(),
            function (data) {
                if (data.exito) {
                    top.successModalEvent(data);
                } else {
                    top.notification({
                        message: data.mensaje,
                        type: 'error'
                    });
                }
            },
            'json');
    });

    $('#filtro_fecha').on('select2:select', function (e) {
        $('#fecha_inicial')
            .data('DateTimePicker')
            .clear();
        $('#fecha_final')
            .data('DateTimePicker')
            .clear();
        $('#date_container').hide();

        let today = moment().set({
            hour: 0,
            minute: 0,
            second: 0,
            millisecond: 0
        });

        switch (e.params.data.id) {
            case '2':
                var initial = today.clone();
                var final = today.clone();
                break;
            case '3':
                var initial = today.clone().subtract(1, 'days');
                var final = today.clone().subtract(1, 'days');
                break;
            case '4':
                var initial = today.clone().subtract(7, 'days');
                var final = today.clone();
                break;
            case '5':
                var initial = today.clone().subtract(30, 'days');
                var final = today.clone();
                break;
            case '6':
                var initial = today.clone().subtract(90, 'days');
                var final = today.clone();
                break;
            default:
                $('#date_container').show();
                break;
        }

        if (initial && final) {
            $('#fecha_inicial')
                .data('DateTimePicker')
                .defaultDate(initial);
            $('#fecha_final')
                .data('DateTimePicker')
                .defaultDate(final);
        }
    });

    function createPicker() {
        $('#fecha_inicial,#fecha_final').datetimepicker({
            locale: 'es',
            format: 'YYYY-MM-DD'
        });
    }
});
