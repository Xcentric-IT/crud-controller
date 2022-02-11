<?php

declare(strict_types=1);

namespace XcentricItFoundation\LaravelCrudController\Actions\Crud;

use XcentricItFoundation\LaravelCrudController\Actions\ActionPayloadInterface;
use XcentricItFoundation\LaravelCrudController\Actions\ExecutableActionResponseContract;
use XcentricItFoundation\LaravelCrudController\Events\CrudModelAfterUpdate;
use XcentricItFoundation\LaravelCrudController\Events\CrudModelBeforeUpdate;

class Update extends Create
{
    public function run(ActionPayloadInterface $actionPayload): ExecutableActionResponseContract
    {
        CrudModelBeforeUpdate::dispatch($actionPayload);
        $actionResponse = $this->doRun($actionPayload);
        CrudModelAfterUpdate::dispatch($actionPayload);

        return $actionResponse;
    }
}
