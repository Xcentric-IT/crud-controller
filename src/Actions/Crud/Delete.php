<?php

declare(strict_types=1);

namespace XcentricItFoundation\LaravelCrudController\Actions\Crud;

use XcentricItFoundation\LaravelCrudController\Actions\ActionPayloadInterface;
use XcentricItFoundation\LaravelCrudController\Actions\ActionResponse;
use XcentricItFoundation\LaravelCrudController\Actions\ExecutableActionResponseContract;
use XcentricItFoundation\LaravelCrudController\Events\CrudModelAfterDelete;
use XcentricItFoundation\LaravelCrudController\Events\CrudModelBeforeDelete;

class Delete extends CrudAction
{
    public function run(ActionPayloadInterface $actionPayload): ExecutableActionResponseContract
    {
        CrudModelBeforeDelete::dispatch($actionPayload);
        $actionResponse = new ActionResponse($actionPayload->getModel()->delete());
        CrudModelAfterDelete::dispatch($actionPayload);

        return $actionResponse;
    }
}
