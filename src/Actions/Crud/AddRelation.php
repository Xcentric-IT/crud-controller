<?php

declare(strict_types=1);

namespace XcentricItFoundation\LaravelCrudController\Actions\Crud;

use XcentricItFoundation\LaravelCrudController\Actions\ActionPayloadInterface;
use XcentricItFoundation\LaravelCrudController\Actions\ActionResponse;
use XcentricItFoundation\LaravelCrudController\Actions\ExecutableActionResponseContract;

class AddRelation extends CrudAction
{
    public function run(ActionPayloadInterface $actionPayload): ExecutableActionResponseContract
    {
        $modelData = $actionPayload->modelData;
        $model = $actionPayload->model;
        $data = $actionPayload->getData();

        $this->entityRelationService->addRemoveRelationships($model, $modelData, $data);

        return new ActionResponse(true);
    }
}
