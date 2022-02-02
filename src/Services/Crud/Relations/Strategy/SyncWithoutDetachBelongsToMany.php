<?php

declare(strict_types=1);

namespace XcentricItFoundation\LaravelCrudController\Services\Crud\Relations\Strategy;

use Illuminate\Database\Eloquent\Model;
use XcentricItFoundation\LaravelCrudController\Services\Crud\Relations\Contract\SyncStrategyContract;
use XcentricItFoundation\LaravelCrudController\Services\Crud\Relations\PivotDataService;

class SyncWithoutDetachBelongsToMany implements SyncStrategyContract
{
    public function __construct(
        protected PivotDataService $pivotDataService,
    ) {
    }

    public function __invoke(Model $model, string $relationName, array $data): void
    {
        if (!isset($data['id'])) {
            return;
        }

        $id = $data['id'];
        $relation = $model->$relationName();

        /** @var Model $subModel */
        $subModel = $relation->getRelated();
        $subModel = $subModel->newModelQuery()->find($id);

        if ($subModel === null) {
            return;
        }

        $subModel->fill($data)->save();

        $pivotData = $this->pivotDataService->cleanup($relation, $data['pivot'] ?? []);

        $relation->syncWithoutDetaching([
            $subModel->getKey() => $pivotData,
        ]);
    }
}
