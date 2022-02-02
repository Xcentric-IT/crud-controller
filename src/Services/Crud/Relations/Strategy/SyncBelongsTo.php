<?php

declare(strict_types=1);

namespace XcentricItFoundation\LaravelCrudController\Services\Crud\Relations\Strategy;

use Illuminate\Database\Eloquent\Model;
use XcentricItFoundation\LaravelCrudController\Services\Crud\Relations\Contract\SyncStrategyContract;

class SyncBelongsTo implements SyncStrategyContract
{
    public function __invoke(Model $model, string $relationName, array $data): void
    {
        $model->$relationName()->associate($data['id'] ?? null);
    }
}
