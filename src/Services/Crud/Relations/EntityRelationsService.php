<?php

declare(strict_types=1);

namespace XcentricItFoundation\LaravelCrudController\Services\Crud\Relations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use XcentricItFoundation\LaravelCrudController\Services\Crud\Relations\Strategy\DetachBelongsToMany;
use XcentricItFoundation\LaravelCrudController\Services\Crud\Relations\Strategy\SyncWithoutDetachBelongsToMany;
use XcentricItFoundation\LaravelCrudController\Services\RelationFieldCheckerService;

class EntityRelationsService
{
    private const ADD_REMOVE_RELATIONS = [
        BelongsToMany::class,
        MorphToMany::class
    ];

    public function __construct(
        protected RelationFieldCheckerService $relationFieldCheckerService,
        protected SyncRelationService $syncRelationService,
    ) {
    }

    public function resolveRelationFields(Model $model, array $data): array
    {
        $parsedData = [];
        $parsedRelationData = [];

        foreach ($data as $key => $item) {
            if (!$model->isRelation($key)) {
                $parsedData[$key] = $item;
                continue;
            }

            $foreignKey = $key . '_id';

            if ($model->isFillable($foreignKey)) {
                $parsedData[$foreignKey] = (is_array($item) && array_key_exists('id', $item))? $item['id'] : $item;
                continue;
            }

            if (!$model->isGuarded($foreignKey)) {
                $parsedRelationData[$key] = $item;
            }
        }

        return [$parsedData, $parsedRelationData];
    }

    public function fillRelationships(Model $model, array $data): void
    {
        foreach ($data as $field => $value) {
            $this->syncRelationService->applySync($model, $field, $value);
        }
    }

    public function fillRelationshipsRecursively(Model $model, array $data, bool $withNewRelationEntries = false): void
    {
        [$modelData, $relations] = $this->resolveRelationFields($model, $data);

        foreach ($relations as $field => $value) {
            $this->syncRelationService->applySyncRecursively($model, $field, $value, $withNewRelationEntries);
        }
    }

    public function addRemoveRelationships(Model $model, array $data, array $params): void
    {
        $relationField = $params['relationField'];
        $add = $params['add'] ?? true;

        if (!$this->relationFieldCheckerService->isRelationField($model, $relationField)) {
            return;
        }

        $relationClass = $this->relationFieldCheckerService->getRelationClassByField($model, $relationField);
        if (!in_array($relationClass, self::ADD_REMOVE_RELATIONS, true)) {
            return;
        }

        $strategyClass = $add
            ? SyncWithoutDetachBelongsToMany::class
            : DetachBelongsToMany::class;

        resolve($strategyClass)(
            $model,
            $relationField,
            $data,
        );
    }
}
