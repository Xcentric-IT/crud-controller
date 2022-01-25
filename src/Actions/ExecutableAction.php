<?php

declare(strict_types=1);

namespace XcentricItFoundation\LaravelCrudController\Actions;

interface ExecutableAction
{
    public function run(
        ActionPayloadInterface $actionPayload
    ): ExecutableActionResponseContract;
}
