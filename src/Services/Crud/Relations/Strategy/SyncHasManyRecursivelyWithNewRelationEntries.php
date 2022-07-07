<?php

declare(strict_types=1);

namespace XcentricItFoundation\LaravelCrudController\Services\Crud\Relations\Strategy;

use Illuminate\Database\Eloquent\Model;

class SyncHasManyRecursivelyWithNewRelationEntries extends SyncHasManyRecursively
{
    protected function prepareRelationData(array $data): array
    {
        unset($data['id']);
        return $data;
    }

    protected function resolveRelationFields(Model $model, array $item, array $idMapping): array
    {
        [$subModelData, $relations] = $this->entityRelationsService->resolveRelationFields($model, $item);

        if (isset($subModelData['parent_id'])) {
            $subModelData['parent_id'] =  $idMapping[$subModelData['parent_id']];
        }

        return [$subModelData, $relations];
    }

    protected function fillRelationships(Model $model, array $relations): void
    {
        $this->entityRelationsService->fillRelationshipsRecursively($model, $relations, true);
    }
}
