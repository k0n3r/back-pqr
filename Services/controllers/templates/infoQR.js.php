<?php
$code = <<<JAVASCRIPT
$(function () {
    var d = getVariableFromUrl('data');
    if (!d) {
        window.notification({
            title: 'Error!',
            icon: 'fa fa-exclamation-circle',
            timeout: 5000,
            color: 'red',
            message: 'La URL no esta disponible o ha sido removida'
        });
        setTimeout(function () { window.location.href = "404.html" }, 5000);
        return;
    }

    let options = {
            selector: '#timeline-container',
            source: function () {
                let data = new Array();
                $.ajax({
                    method:'get',
                    url: window.baseUrl + `api/pqr/historyForTimeline`,
                    async: false,
                    data: {
                        infoCryp: d
                    },
                }).done((response) => {
                    if (!response.success) {
                        window.notification({
                            title: 'Error!',
                            icon: 'fa fa-exclamation-circle',
                            timeout: 5000,
                            color: 'red',
                            message: response.message
                        });
                        setTimeout(function () { window.location.href = "404.html" }, 5000);
                        return;
                    } 
                       
                    data = response.data;
                }).fail(function () {
                    console.error(...arguments)
                });

                return data;
            }
        };

        TimeLine = new TimeLine(options);
        TimeLine.init();

});
JAVASCRIPT;
echo $code;
