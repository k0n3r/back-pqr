<?php

namespace App\Bundles\pqr\Event;

use App\Bundles\pqr\Services\PqrFormFieldService;
use Symfony\Contracts\EventDispatcher\Event;

class PqrFormFieldEvent extends Event
{
    /**
     * @var PqrFormFieldService
     */
    private PqrFormFieldService $Service;

    /**
     * PqrFormFieldService constructor.
     *
     * @param PqrFormFieldService $service
     */
    public function __construct(PqrFormFieldService $service)
    {
        $this->setService($service);
    }

    /**
     * @return PqrFormFieldService
     */
    public function getService(): PqrFormFieldService
    {
        return $this->Service;
    }

    /**
     * @param PqrFormFieldService $Service
     */
    public function setService(PqrFormFieldService $Service): void
    {
        $this->Service = $Service;
    }
}