<?php

declare(strict_types=1);

namespace XcentricItFoundation\LaravelCrudController\Actions\Crud;

use XcentricItFoundation\LaravelCrudController\Actions\ActionPayloadInterface;
use XcentricItFoundation\LaravelCrudController\Actions\ActionResponse;
use XcentricItFoundation\LaravelCrudController\Actions\ExecutableAction;
use XcentricItFoundation\LaravelCrudController\Actions\ExecutableActionResponseContract;

class Create extends CrudAction implements ExecutableAction
{

    public function run(ActionPayloadInterface $actionPayload): ExecutableActionResponseContract
    {
        $data = $actionPayload->data;
        $model = $actionPayload->model;

        [$data, $relations] = $this->entityRelationService->resolveRelationFields($actionPayload->model, $data);
        $model->fill($data)->save();
        $this->entityRelationService->fillRelationships($model, $relations);

        return new ActionResponse(true);
    }
}
