<?php

declare(strict_types=1);

namespace App\Bundles\pqr\Service;

use App\Bundles\pqr\Services\models\PqrFormField as LegacyPqrFormField;
use App\Bundles\pqr\Services\PqrFormFieldService;

final class PqrFormFieldServiceFactory
{
    public function create(int $id = 0): PqrFormFieldService
    {
        return (new LegacyPqrFormField($id))->getService();
    }
}
