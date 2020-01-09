<?php

namespace Saia\Pqr\Models;

class PqrFormField extends \Model
{

    public function __construct($id = null)
    {
        parent::__construct($id);
    }

    protected function defineAttributes()
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
}
