<?php

declare(strict_types=1);

namespace XcentricItFoundation\LaravelCrudController\Services\Crud\Relations\Strategy;

use Illuminate\Database\Eloquent\Model;

class SyncHasManyRecursivelyWithNewRelationEntries extends SyncHasManyRecursively
{
    //TODO: remove this method once parent auto sync is implemented to work with new entries
    protected function buildSortedList(array $elements): array
    {
        return $elements;
    }

    protected function prepareRelationData(array $data): array
    {
        unset($data['id']);
        return $data;
    }

    //TODO: uncomment this method once parent auto sync is implemented to work with new entries
//    protected function resolveRelationFields(Model $model, array $item, array $idMapping): array
//    {
//        if (config('laravel-crud-controller.auto_sync_parent_relations') === false) {
//            return parent::resolveRelationFields($model, $item, $idMapping);
//        }
//
//        [$subModelData, $relations] = $this->entityRelationsService->resolveRelationFields($model, $item);
//
//        if (isset($subModelData['parent_id'])) {
//            $subModelData['parent_id'] =  $idMapping[$subModelData['parent_id']];
//        }
//
//        return [$subModelData, $relations];
//    }

    protected function fillRelationships(Model $model, array $relations): void
    {
        $this->entityRelationsService->fillRelationshipsRecursively($model, $relations, true);
    }
}
