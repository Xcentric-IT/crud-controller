<?php

declare(strict_types=1);

namespace XcentricItFoundation\LaravelCrudController\Actions\Crud;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use XcentricItFoundation\LaravelCrudController\Actions\ActionPayloadInterface;

class MassCreateOrUpdate extends MassUpdate
{
    /**
     * @throws ValidationException
     */
    protected function doRun(ActionPayloadInterface $actionPayload): bool
    {
        /** @var Create $createAction */
        $createAction = resolve(Create::class);

        /** @var Update $updateAction */
        $updateAction = resolve(Update::class);

        foreach ($actionPayload->getData() as $item) {
            $id = $item['id'] ?? null;

            Validator::make($item, $this->request->rules())->validate();

            /** @var Model $model */
            $model = $actionPayload->getModel()->newQuery()->findOrNew($id);

            if ($model->exists) {
                $data = $this->mergeModelData($model, $item);

                $crudActionPayload = $this->createPayload($data, $model);
                $updateAction->run($crudActionPayload);
                continue;
            }

            $crudActionPayload = $this->createPayload($item, $model);
            $createAction->run($crudActionPayload);
        }

        return true;
    }
}
