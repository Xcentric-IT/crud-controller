<?php

declare(strict_types=1);

namespace XcentricItFoundation\LaravelCrudController\Services\Crud\Relations\Contract;

use Illuminate\Database\Eloquent\Model;

interface SyncStrategyContract
{
    public function __invoke(Model $model, string $relationName, array $data): void;
}
