<?php

declare(strict_types=1);

namespace XcentricItFoundation\LaravelCrudController\Services\Crud\Relations\Strategy;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use XcentricItFoundation\LaravelCrudController\Services\Crud\Relations\Contract\SyncStrategyContract;

class SyncBelongsTo implements SyncStrategyContract
{
    public function __invoke(Model $model, string $relationName, array|null $data): void
    {
        $relation = $this->getRelation($model, $relationName);

        $id = $data['id'] ?? null;

        if ($data === null || $id === null) {
            $relation->dissociate();
            return;
        }

        $relation->associate($id);
    }

    protected function getRelation(Model $model, string $relationName): BelongsTo
    {
        return $model->$relationName();
    }
}
