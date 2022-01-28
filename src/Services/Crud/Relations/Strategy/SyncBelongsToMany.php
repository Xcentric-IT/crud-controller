<?php

declare(strict_types=1);

namespace XcentricItFoundation\LaravelCrudController\Services\Crud\Relations\Strategy;

use Illuminate\Database\Eloquent\Model;
use XcentricItFoundation\LaravelCrudController\Services\Crud\Relations\PivotDataService;

class SyncBelongsToMany
{
    public function __construct(
        protected PivotDataService $pivotDataService,
    ) {
    }

    public function __invoke(
        Model $model,
        string $relationName,
        array $data,
    ) {
        $relation = $model->$relationName();

        $syncIds = [];

        foreach ($data as $related) {
            $id = $related['id'] ?? null;
            $pivotData = $this->pivotDataService->cleanup($relation, $related['pivot'] ?? []);

            /** @var Model $subModel */
            $subModel = $relation->getRelated();
            $subModel = $subModel->newModelQuery()->find($id) ?? $subModel;

            if (isset($related['DIRTY'])) {
                /* @phpstan-ignore-next-line */
                $subModel->fill($related)->save();
            }

            /* @phpstan-ignore-next-line */
            $syncIds[$subModel->getKey()] = $pivotData;
        }

        $relation->sync($syncIds);
    }
}
