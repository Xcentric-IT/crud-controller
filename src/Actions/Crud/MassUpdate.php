<?php

declare(strict_types=1);

namespace XcentricItFoundation\LaravelCrudController\Actions\Crud;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use XcentricItFoundation\LaravelCrudController\Actions\ActionPayloadInterface;
use XcentricItFoundation\LaravelCrudController\LaravelCrudRequest;
use XcentricItFoundation\LaravelCrudController\Services\LoadModelDataMissingFromRequest;

class MassUpdate extends TransactionableAction
{
    public function __construct(
        protected LoadModelDataMissingFromRequest $mergeModelDataToRequest,
        protected LaravelCrudRequest $request,
    ) {
    }

    /**
     * @throws ValidationException
     */
    protected function doRun(ActionPayloadInterface $actionPayload): bool
    {
        /** @var Update $updateAction */
        $updateAction = resolve(Update::class);

        foreach ($actionPayload->getData() as $item) {
            $id = $item['id'] ?? null;

            /** @var Model $model */
            $model = $actionPayload->getModel()->newQuery()->findOrFail($id);

            $data = $this->mergeModelData($model, $item);

            Validator::make($data, $this->request->rules())->validate();

            $crudActionPayload = $this->createPayload($item, $model);
            $updateAction->run($crudActionPayload);
        }

        return true;
    }

    protected function mergeModelData(Model $model, array $item): array
    {
        if (config('laravel-crud-controller.merge_model_data_to_request') !== true) {
            return $item;
        }

        $modelData = $this->mergeModelDataToRequest->load($model, get_class($this->request));

        return array_merge($modelData, $item);
    }

    protected function createPayload(array $item, Model $model): ActionPayloadInterface
    {
        return new CrudActionPayload($item, $model);
    }
}
