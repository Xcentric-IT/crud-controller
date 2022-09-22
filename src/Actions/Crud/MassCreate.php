<?php

declare(strict_types=1);

namespace XcentricItFoundation\LaravelCrudController\Actions\Crud;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use XcentricItFoundation\LaravelCrudController\Actions\ActionPayloadInterface;
use XcentricItFoundation\LaravelCrudController\LaravelCrudRequest;

class MassCreate extends TransactionableAction
{
    public function __construct(
        protected LaravelCrudRequest $request,
    ) {
    }

    /**
     * @throws ValidationException
     */
    protected function doRun(ActionPayloadInterface $actionPayload): bool
    {
        /** @var Create $createAction */
        $createAction = resolve(Create::class);

        foreach ($actionPayload->getData() as $item) {
            Validator::make($item, $this->request->rules())->validate();

            $model = $actionPayload->getModel()->newInstance();

            $crudActionPayload = new CrudActionPayload($item, $model);
            $createAction->run($crudActionPayload);
        }

        return true;
    }
}
