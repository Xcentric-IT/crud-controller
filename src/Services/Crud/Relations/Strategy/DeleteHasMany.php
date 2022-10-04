<?php

declare(strict_types=1);

namespace XcentricItFoundation\LaravelCrudController\Services\Crud\Relations\Strategy;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use XcentricItFoundation\LaravelCrudController\Services\Crud\Relations\Contract\SyncStrategyContract;

class DeleteHasMany implements SyncStrategyContract
{
    public function __invoke(Model $model, string $relationName, array $data): void
    {
        if (!isset($data['id'])) {
            return;
        }

        $relation = $this->getRelation($model, $relationName);
        $subModelClass = $relation->getRelated();

        $id = $data['id'];

        /** @var Model $subModel */
        $subModel = $subModelClass->newModelQuery()->findOrFail($id);
        $subModel->delete();
    }

    protected function getRelation(Model $model, string $relationName): HasMany
    {
        return $model->$relationName();
    }
}
