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
    
    if (!localStorage.getItem('WsKey')) {
        window.getCredentials();
    }

    let options = {
            selector: '#timeline-container',
            source: function () {
                let data = new Array();
                $.ajax({
                    url: window.baseUrl + `app/modules/back_pqr/app/request.php`,
                    async: false,
                    data: {
                        key: localStorage.getItem('WsKey'),
                        token: localStorage.getItem('WsToken'),
                        class: 'FtPqrController',
                        method: 'getHistoryForTimeLine',
                        data: {infoCryp: d}
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
