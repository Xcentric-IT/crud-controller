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
use XcentricItFoundation\LaravelCrudController\Services\Crud\Relations\Strategy\SyncHasManyRecursively;
use XcentricItFoundation\LaravelCrudController\Services\Crud\Relations\Strategy\SyncHasManyRecursivelyWithNewRelationEntries;
use XcentricItFoundation\LaravelCrudController\Services\RelationFieldCheckerService;

class SyncRelationService
{
    protected const RELATIONS_WITH_RECURSIVE_SAVE_ALLOWED = [
        HasMany::class,
        MorphMany::class,
    ];

    public function __construct(
        protected RelationFieldCheckerService $relationFieldCheckerService,
    ) {
    }

    public function applySync(Model $model, string $field, array|null $value): void
    {
        $syncStrategyClass = $this->resolveSyncStrategy($model, $field);

        if ($syncStrategyClass === null) {
            return;
        }

        resolve($syncStrategyClass)(
            $model,
            $field,
            $value,
        );
    }

    public function applySyncRecursively(Model $model, string $field, array|null $value, bool $withNewRelationEntries): void
    {
        $syncStrategyClass = $this->resolveSyncStrategy($model, $field);

        if ($syncStrategyClass === null) {
            return;
        }

        $relationClass = $this->relationFieldCheckerService->getRelationClassByField($model, $field);
        if (in_array($relationClass, self::RELATIONS_WITH_RECURSIVE_SAVE_ALLOWED, true)) {
            $syncStrategyClass = SyncHasManyRecursively::class;
            if ($withNewRelationEntries === true) {
                $syncStrategyClass = SyncHasManyRecursivelyWithNewRelationEntries::class;
            }
        }

        resolve($syncStrategyClass)(
            $model,
            $field,
            $value,
        );
    }

    protected function resolveSyncStrategy(Model $model, string $field): ?string
    {
        if (!$this->relationFieldCheckerService->isRelationField($model, $field)) {
            return null;
        }

        $relationClass = $this->relationFieldCheckerService->getRelationClassByField($model, $field);

        return match ($relationClass) {
            BelongsToMany::class, MorphToMany::class => SyncBelongsToMany::class,
            HasMany::class, MorphMany::class => SyncHasMany::class,
            BelongsTo::class => SyncBelongsTo::class,
            default => null,
        };
    }
}
