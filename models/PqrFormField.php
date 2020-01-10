<?php

namespace Saia\Pqr\Models;

class PqrFormField extends \Model
{

    public function __construct($id = null)
    {
        parent::__construct($id);
    }

    protected function defineAttributes(): void
    {
        $this->dbAttributes = (object) [
            'safe' => [
                'name',
                'label',
                'required',
                'setting',
                'fk_pqr_html_field',
                'active'
            ],
            'primary' => 'id',
            'table' => 'pqr_form_fields'
        ];
    }

    public function getDataAttributes(): array
    {
        $attributes = $this->getSafeAttributes();
        array_push($attributes, $this->getPkName());

        $data = [];
        foreach ($attributes as $value) {

            $Stringy = new \Stringy\Stringy("get_{$value}");
            $method = (string) $Stringy->upperCamelize();
            $data[$value] = (method_exists($this, $method)) ? $this->$method($this->$value) : $this->$value;
        }

        return $data;
    }

    public function getSetting(string $value): object
    {
        return json_decode($value);
    }

    public function getFkPqrHtmlField(int $id): array
    {
        $PqrHtmlField = new PqrHtmlField($id);

        return $PqrHtmlField->getAttributes();
    }
}
