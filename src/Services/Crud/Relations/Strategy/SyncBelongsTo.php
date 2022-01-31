<?php

declare(strict_types=1);

namespace XcentricItFoundation\LaravelCrudController\Services\Crud\Relations\Strategy;

use Illuminate\Database\Eloquent\Model;

class SyncBelongsTo
{
    public function __invoke(Model $model, string $relationshipName, array $data): void
    {
        $model->$relationshipName()->associate($data['id'] ?? null);
    }
}
