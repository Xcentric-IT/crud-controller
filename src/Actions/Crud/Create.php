<?php

declare(strict_types=1);

namespace XcentricItFoundation\LaravelCrudController\Actions\Crud;

use XcentricItFoundation\LaravelCrudController\Actions\ActionPayloadInterface;
use XcentricItFoundation\LaravelCrudController\Actions\ActionResponse;
use XcentricItFoundation\LaravelCrudController\Actions\ExecutableActionResponseContract;
use XcentricItFoundation\LaravelCrudController\Events\CrudModelAfterCreate;
use XcentricItFoundation\LaravelCrudController\Events\CrudModelBeforeCreate;

class Create extends CrudAction
{
    public function run(ActionPayloadInterface $actionPayload): ExecutableActionResponseContract
    {
        CrudModelBeforeCreate::dispatch($actionPayload);
        $actionResponse = $this->doRun($actionPayload);
        CrudModelAfterCreate::dispatch($actionPayload);

        return $actionResponse;
    }

    protected function doRun(ActionPayloadInterface $actionPayload): ExecutableActionResponseContract
    {
        $data = $actionPayload->getData();
        $model = $actionPayload->getModel();

        [$data, $relations] = $this->entityRelationService->resolveRelationFields($model, $data);
        $model->fill($data)->save();
        $this->entityRelationService->fillRelationships($model, $relations);

        return new ActionResponse(true);
    }
}
