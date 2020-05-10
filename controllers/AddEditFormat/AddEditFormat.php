<?php

namespace Saia\Pqr\controllers\addEditFormat;

use Saia\Pqr\controllers\addEditFormat\IAddEditFormat;

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
