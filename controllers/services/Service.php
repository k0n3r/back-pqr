<?php

namespace Saia\Pqr\controllers\services;

use Saia\core\model\Model;

abstract class Service
{
    protected $Model;

    public function __construct(Model $Model)
    {
        $this->Model = $Model;
    }
}
