$(function () {
    let baseUrl = $('script[data-baseurl]').data('baseurl');
    (function init() {

        $('#filtro_fecha').select2();
        createPicker();
        $("#morefields").empty().load(`${baseUrl}app/modules/back_pqr/formatos/pqr/buscar.php`);

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

    $('#find_document_form').on('submit', function (e) {
        e.preventDefault();

        top.notification({
            type: 'info',
            message: 'Esto puede tardar un momento'
        });


        // let variableBusqueda = {
        //     'funcionario': $("#funcionario").val()
        // };
        // if ($("#isAdmin").prop('checked')) {
        //     variableBusqueda.isAdmin = 1;
        // }
        // $("#variable_busqueda").val(JSON.stringify(variableBusqueda));

        // $.post(
        //     `${baseUrl}app/busquedas/procesa_filtro_busqueda.php`,
        //     $('#find_document_form').serialize(),
        //     function (response) {
        //         if (response.exito) {
        //             let route = baseUrl + response.url;
        //             $('#iframe_workspace').attr('src', route);
        //         } else {
        //             top.notification({
        //                 type: 'error',
        //                 message: response.message
        //             });
        //         }
        //     },
        //     'json'
        // );

        // $('#dinamic_modal').modal('hide');
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
