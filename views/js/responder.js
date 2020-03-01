$(function () {
    let params = $('#scriptResponder').data('params');
    $('#scriptResponder').removeAttr('data-params');

    $("#iddocumento").select2();

    $("[name='type']").change(function () {
        if ($(this).val() == 1) {
            $("#divSelect").show();
        } else {
            $("#divSelect").hide();
        }
    });


});