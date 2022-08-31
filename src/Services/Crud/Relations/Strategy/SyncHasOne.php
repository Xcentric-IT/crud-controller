<?php

declare(strict_types=1);

namespace XcentricItFoundation\LaravelCrudController\Services\Crud\Relations\Strategy;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use XcentricItFoundation\LaravelCrudController\Services\Crud\Relations\Contract\SyncStrategyContract;

class SyncHasOne implements SyncStrategyContract
{
    public function __invoke(Model $model, string $relationName, array|null $data): void
    {
        $relation = $this->getRelation($model, $relationName);

        if ($data === null) {
            $relation->delete();
            return;
        }

        $subModelClass = $relation->getRelated();

        $id = $data['id'] ?? null;

        $subModel = $id !== null
            ? $subModelClass->newModelQuery()->find($id)
            : null;

        if (!$subModel instanceof Model) {
            $relation->create($data);
            return;
        }

        $subModel->fill($data);
        $relation->save($subModel);
    }

    protected function getRelation(Model $model, string $relationName): HasOne
    {
        return $model->$relationName();
    }
}
