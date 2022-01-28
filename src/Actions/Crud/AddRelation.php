<?php

declare(strict_types=1);

namespace XcentricItFoundation\LaravelCrudController\Actions\Crud;

use XcentricItFoundation\LaravelCrudController\Actions\ActionPayloadInterface;
use XcentricItFoundation\LaravelCrudController\Actions\ActionResponse;
use XcentricItFoundation\LaravelCrudController\Actions\ExecutableActionResponseContract;
use XcentricItFoundation\LaravelCrudController\Events\CrudModelAfterAddRelation;
use XcentricItFoundation\LaravelCrudController\Events\CrudModelBeforeAddRelation;

class AddRelation extends CrudAction
{
    public function run(ActionPayloadInterface $actionPayload): ExecutableActionResponseContract
    {
        CrudModelBeforeAddRelation::dispatch($actionPayload);
        $actionResponse = $this->doRun($actionPayload);
        CrudModelAfterAddRelation::dispatch($actionPayload);

        return $actionResponse;
    }

    protected function doRun(ActionPayloadInterface $actionPayload): ExecutableActionResponseContract
    {
        $data = $actionPayload->getData();
        $model = $actionPayload->getModel();
        $additionalData = $actionPayload->getAdditionalData();

        $this->entityRelationService->addRemoveRelationships($model, $data, $additionalData);

        return new ActionResponse(true);
    }
}
