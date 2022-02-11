<?php

declare(strict_types=1);

namespace XcentricItFoundation\LaravelCrudController\Actions\Crud;

use XcentricItFoundation\LaravelCrudController\Actions\ActionPayloadInterface;
use XcentricItFoundation\LaravelCrudController\Actions\ExecutableActionResponseContract;
use XcentricItFoundation\LaravelCrudController\Events\CrudModelAfterRemoveRelation;
use XcentricItFoundation\LaravelCrudController\Events\CrudModelBeforeRemoveRelation;

class RemoveRelation extends AddRelation
{
    public function run(ActionPayloadInterface $actionPayload): ExecutableActionResponseContract
    {
        CrudModelBeforeRemoveRelation::dispatch($actionPayload);
        $actionResponse = $this->doRun($actionPayload);
        CrudModelAfterRemoveRelation::dispatch($actionPayload);

        return $actionResponse;
    }
}
