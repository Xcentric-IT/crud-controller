<?php

declare(strict_types=1);

namespace XcentricItFoundation\LaravelCrudController\Actions\Crud;

use Illuminate\Database\Eloquent\Model;
use XcentricItFoundation\LaravelCrudController\Actions\ActionPayloadInterface;

class MassDelete extends TransactionableAction
{

    protected function doRun(ActionPayloadInterface $actionPayload): bool
    {
        /** @var Delete $deleteAction */
        $deleteAction = resolve(Delete::class);

        foreach ($actionPayload->getData() as $id) {
            /** @var Model $model */
            $model = $actionPayload->getModel()->newQuery()->findOrFail($id);

            $crudActionPayload = new CrudActionPayload([], $model);
            $deleteAction->run($crudActionPayload);
        }

        return true;
    }
}
