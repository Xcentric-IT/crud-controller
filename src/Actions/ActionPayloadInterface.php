<?php

declare(strict_types=1);

namespace XcentricItFoundation\LaravelCrudController\Actions;

use Illuminate\Database\Eloquent\Model;

interface ActionPayloadInterface
{
    public function getData(): array;

    public function getOriginalData(): array;

    public function getModel(): Model;

    public function getAdditionalData(): array;
}
