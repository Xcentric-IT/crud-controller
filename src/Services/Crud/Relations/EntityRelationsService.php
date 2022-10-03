<?php

declare(strict_types=1);

namespace XcentricItFoundation\LaravelCrudController\Services\Crud\Relations;

use Illuminate\Database\Eloquent\Model;
use XcentricItFoundation\LaravelCrudController\Services\RelationFieldCheckerService;

class EntityRelationsService
{
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

            if (!$model->isFillable($key) && $model->isGuarded($foreignKey)) {
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

        $add
            ? $this->syncRelationService->createRelation($model, $relationField, $data)
            : $this->syncRelationService->deleteRelation($model, $relationField, $data);
    }
}
