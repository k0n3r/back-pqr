<?php

namespace App\Bundles\pqr\Services\controllers;

use Saia\controllers\generator\webservice\WsFt;

class WebserviceCalificacion extends WsFt
{
    protected function getCodeJsResponse(): string
    {
        return <<<JS
window.notification({
    title: "¡Muchas gracias por su tiempo!",
    color: 'green',
    position: "center",
    overlay: true,
    timeout: false,
    icon: 'fa fa-check',
    layout: 2,
    message: '<br/>Con esta calificación nos ayuda a mejorar nuestros servicios',
    onClosed: function () {
        window.location.href = '../pqr/index.html';
    }
});
JS;

    }
}
