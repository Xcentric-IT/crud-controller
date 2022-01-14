<?php

declare(strict_types=1);

namespace XcentricItFoundation\LaravelCrudController\Actions;

interface ExecutableActionResponseContract
{
    public function success(): bool;

    public function getData(): array;
}
