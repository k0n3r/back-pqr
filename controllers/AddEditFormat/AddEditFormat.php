<?php

namespace Saia\Pqr\Controllers\AddEditFormat;

use Saia\Pqr\Controllers\AddEditFormat\IAddEditFormat;

class AddEditFormat
{
    const ADD = 'add';
    const ADIT = 'edit';

    protected $Controller;
    protected $addEdit;

    public function __construct(IAddEditFormat $Controller, $addEdit)
    {
        $this->Controller = $Controller;
        $this->addEdit = $addEdit;
    }

    public function generate(): void
    {
        if ($this->addEdit == self::ADD) {
            $this->Controller->createForm();
        } else {
            $this->Controller->updateForm();
        }
        $this->Controller->generateForm();
    }
}
