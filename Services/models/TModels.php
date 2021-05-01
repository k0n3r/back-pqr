<?php

namespace App\Bundles\pqr\Services\models;

use Stringy\Stringy;

trait TModels
{

    /**
     * obtiene los datos de las atributos
     *
     * @return array
     * @author Andres Agudelo <andres.agudelo@cerok.com>
     * @date 2020
     */
    public function getDataAttributes(): array
    {
        $attributes = $this->getSafeAttributes();
        array_push($attributes, $this->getPkName());

        $data = [];
        foreach ($attributes as $value) {

            $Stringy = new Stringy("get_$value");
            $method = (string) $Stringy->upperCamelize();
            $data[$value] = (method_exists($this, $method)) ? $this->$method() : $this->$value;
        }

        return $data;
    }
}
