<?php

declare(strict_types=1);

namespace XcentricItFoundation\LaravelCrudController\Actions;

class ActionResponse implements ExecutableActionResponseContract
{
    public function __construct(
        protected bool $success,
        protected array $data = []
    ) {
    }

    public function success(): bool
    {
        return $this->success;
    }

    public function getData(): array
    {
        return $this->data;
    }
}
