<?php

declare(strict_types=1);

namespace XcentricItFoundation\LaravelCrudController\Actions\Crud;

use App\Actions\ActionPayloadInterface;
use App\Actions\ActionResponse;
use App\Actions\ExecutableAction;
use App\Actions\ExecutableActionResponseContract;

class Create implements ExecutableAction
{
    use CrudActionTrait;

    /**
     * @param CrudActionPayload $actionPayload
     * @return ExecutableActionResponseContract
     */
    public function run(ActionPayloadInterface $actionPayload): ExecutableActionResponseContract
    {
        $data = $actionPayload->data;
        $model = $actionPayload->model;

        [$data, $relations] = $this->resolveRelationFields($actionPayload->model, $data);
        $model->fill($data)->save();
        $this->fillRelationships($model, $relations);

        return new ActionResponse(true);
    }
}
