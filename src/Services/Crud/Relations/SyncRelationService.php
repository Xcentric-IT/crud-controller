<?php

declare(strict_types=1);

namespace XcentricItFoundation\LaravelCrudController\Services\Crud\Relations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use XcentricItFoundation\LaravelCrudController\Services\Crud\Relations\Strategy\SyncBelongsTo;
use XcentricItFoundation\LaravelCrudController\Services\Crud\Relations\Strategy\SyncBelongsToMany;
use XcentricItFoundation\LaravelCrudController\Services\Crud\Relations\Strategy\SyncHasMany;
use XcentricItFoundation\LaravelCrudController\Services\RelationFieldCheckerService;

class SyncRelationService
{
    public function __construct(
        protected RelationFieldCheckerService $relationFieldCheckerService,
    ) {
    }

    public function applySync(Model $model, string $field, array $value): void
    {
        if (!$this->relationFieldCheckerService->isRelationField($model, $field)) {
            return;
        }

        $relation = $this->relationFieldCheckerService->getRelationByField($model, $field);

        $syncStrategyClass = match (get_class($relation)) {
            BelongsToMany::class, MorphToMany::class => SyncBelongsToMany::class,
            HasMany::class, MorphMany::class => SyncHasMany::class,
            BelongsTo::class => SyncBelongsTo::class,
            default => null,
        };

        if ($syncStrategyClass === null) {
            return;
        }

        resolve($syncStrategyClass)(
            $model,
            $this->relationFieldCheckerService->getRelationNameByField($field),
            $value,
        );
    }
}
