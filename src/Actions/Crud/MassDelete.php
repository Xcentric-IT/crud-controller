<?php

declare(strict_types=1);

namespace XcentricItFoundation\LaravelCrudController\Actions\Crud;

use Illuminate\Database\Eloquent\Model;
use XcentricItFoundation\LaravelCrudController\Actions\ActionPayloadInterface;

class MassDelete extends TransactionableAction
{

    protected function doRun(ActionPayloadInterface $actionPayload): bool
    {
        $data = [];
        $ids = $actionPayload->getData();
        foreach ($ids['ids'] as $id) {
            $modelFqn = get_class($actionPayload->getModel());
            /** @var Model $model */
            $model = new $modelFqn;
            $model = $model->newQuery()->findOrFail($id);

            /** @var CrudActionPayload $crudActionPayload */
            $crudActionPayload = new CrudActionPayload($data, $model);

            /** @var Delete $delete */
            $delete = resolve(Delete::class);
            $delete->run($crudActionPayload);
        }

        return true;
    }
}
