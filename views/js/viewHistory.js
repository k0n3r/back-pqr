$(function () {
    let params = $('#scriptHistory').data('params');
    $('#scriptHistory').removeAttr('data-params');

    var baseUrl = localStorage.getItem('baseUrl');

    $("#tableHistory").bootstrapTable({
        url: baseUrl + 'app/modules/back_pqr/app/request.php',
        queryParams: function (queryParams) {
            return {
                key: localStorage.getItem('key'),
                token: localStorage.getItem('token'),
                class: 'PqrHistoryController',
                method: 'getHistory',
                data: {
                    idft: params.idft
                }
            }
        },
        classes: 'table table-hover mt-0',
        theadClasses: 'thead-light',
        columns: [
            {
                field: 'fecha',
                title: 'Fecha',
                align: 'center',
                sortable: true
            },
            {
                field: 'nombre_funcionario',
                title: 'Funcionario',
                align: 'center',
                sortable: true
            },
            {
                field: 'descripcion',
                title: 'Descripción'
            },
        ],
        pagination: true,
        pageSize: 10
    });
});