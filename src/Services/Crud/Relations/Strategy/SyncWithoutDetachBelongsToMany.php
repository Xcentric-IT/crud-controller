<?php

declare(strict_types=1);

namespace XcentricItFoundation\LaravelCrudController\Services\Crud\Relations\Strategy;

use Illuminate\Database\Eloquent\Model;
use XcentricItFoundation\LaravelCrudController\Services\Crud\Relations\PivotDataService;

class SyncWithoutDetachBelongsToMany
{
    public function __construct(
        protected PivotDataService $pivotDataService,
    ) {
    }

    public function __invoke(Model $model, string $relationName, array $data)
    {
        if (!isset($data['id'])) {
            return;
        }

        $syncIds = [];

        $id = $data['id'];
        $relation = $model->$relationName();
        $syncIds[$id] = $this->pivotDataService->cleanup($relation, $data['pivot'] ?? []);

        /** @var Model $subModel */
        $subModel = $relation->getRelated();
        $subModel = $subModel->newModelQuery()->find($id) ?? $subModel;

        if (isset($data['DIRTY'])) {
            $subModel->fill($data)->save();
        }

        $relation->syncWithoutDetaching($syncIds);
    }
}
