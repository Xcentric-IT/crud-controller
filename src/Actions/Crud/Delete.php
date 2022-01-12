<?php

declare(strict_types=1);

namespace XcentricItFoundation\LaravelCrudController\Actions\Crud;

use App\Actions\ActionPayloadInterface;
use App\Actions\ActionResponse;
use App\Actions\ExecutableAction;
use App\Actions\ExecutableActionResponseContract;

class Delete implements ExecutableAction
{
    use CrudActionTrait;

    /**
     * @param CrudActionPayload $actionPayload
     * @return ExecutableActionResponseContract
     */
    public function run(ActionPayloadInterface $actionPayload): ExecutableActionResponseContract
    {
        return new ActionResponse($actionPayload->model->delete());
    }
}
