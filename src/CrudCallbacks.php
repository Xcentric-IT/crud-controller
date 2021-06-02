<?php

namespace XcentricItFoundation\LaravelCrudController;

use Illuminate\Database\Eloquent\Model;

trait CrudCallbacks
{
    protected function beforeCreate(Model $model): void
    {
        return;
    }

    protected function afterCreate(Model $model): void
    {
        return;
    }

    protected function beforeUpdate(Model $model): void
    {
        return;
    }

    protected function afterUpdate(Model $model): void
    {
        return;
    }

    protected function beforeDelete(Model $model): void
    {
        return;
    }

    protected function afterDelete(Model $model): void
    {
        return;
    }
}
