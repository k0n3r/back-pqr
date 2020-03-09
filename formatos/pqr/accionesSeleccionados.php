<?php

function answers(array $data)
{
    $code = <<<HTML
    <select id='actions' class='pull-left btn btn-lg'>
        <option value=''>Acciones...</option>
        <option value='1'>Responder</option>
    </select>
HTML;

    return $code;
}
