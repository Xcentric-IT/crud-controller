<?php

declare(strict_types=1);

namespace XcentricItFoundation\LaravelCrudController\Services\Crud\Relations\Strategy;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use XcentricItFoundation\LaravelCrudController\Services\Crud\Relations\Contract\SyncStrategyContract;
use XcentricItFoundation\LaravelCrudController\Services\Crud\Relations\PivotDataService;

class SyncBelongsToMany implements SyncStrategyContract
{
    public function __construct(
        protected PivotDataService $pivotDataService,
    ) {
    }

    public function __invoke(Model $model, string $relationName, array $data): void
    {
        $relation = $this->getRelation($model, $relationName);
        $subModelClass = $relation->getRelated();

        $syncIds = [];

        foreach ($data as $related) {
            $id = $related['id'] ?? null;
            $pivotData = $this->pivotDataService->cleanup($relation, $related['pivot'] ?? []);

            /** @var Model $subModel */
            $subModel = $subModelClass->newModelQuery()->findOrNew($id);
            $subModel->fill($related)->save();

            $syncIds[$subModel->getKey()] = $pivotData;
        }

        $relation->sync($syncIds);
    }

    protected function getRelation(Model $model, string $relationName): BelongsToMany
    {
        return $model->$relationName();
    }
}
