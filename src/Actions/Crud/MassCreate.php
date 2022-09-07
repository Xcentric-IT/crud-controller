<?php

declare(strict_types=1);

namespace XcentricItFoundation\LaravelCrudController\Actions\Crud;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use XcentricItFoundation\LaravelCrudController\Actions\ActionPayloadInterface;
use XcentricItFoundation\LaravelCrudController\LaravelCrudRequest;

class MassCreate extends TransactionableAction
{
    public function __construct(
        protected LaravelCrudRequest $request,
    ) {
    }

    protected function doRun(ActionPayloadInterface $actionPayload): bool
    {
        foreach ($actionPayload->getData() as $payloadData) {
            Validator::make($payloadData, $this->request->rules())->validate();

            $modelFqn = get_class($actionPayload->getModel());
            /** @var Model $model */
            $model = new $modelFqn;

            /** @var CrudActionPayload $crudActionPayload */
            $crudActionPayload = new CrudActionPayload($payloadData, $model);

            /** @var Create $create */
            $create = resolve(Create::class);
            $create->run($crudActionPayload);
        }

        return true;
    }
}
