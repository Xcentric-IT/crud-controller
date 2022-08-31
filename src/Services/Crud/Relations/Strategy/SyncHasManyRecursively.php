<?php

declare(strict_types=1);

namespace XcentricItFoundation\LaravelCrudController\Services\Crud\Relations\Strategy;

use Illuminate\Database\Eloquent\Model;
use XcentricItFoundation\LaravelCrudController\Services\Crud\Relations\EntityRelationsService;

class SyncHasManyRecursively extends SyncHasMany
{
    public function __construct(
        protected EntityRelationsService $entityRelationsService
    ) {
    }

    public function __invoke(Model $model, string $relationName, array $data): void
    {
        $relation = $this->getRelation($model, $relationName);

        $unSyncedSubModels = $relation->pluck('id')->all();
        $subModelClass = $relation->getRelated();

        $relationsData = $this->buildSortedList($data);

        $newSubModelsIdMapping = [];

        foreach ($relationsData as $item) {
            $subModelId = $item['id'] ?? null;

            $item = $this->prepareRelationData($item);

            $id = $item['id'] ?? null;

            $subModel = $id !== null
                ? $subModelClass->newModelQuery()->find($id)
                : null;

            [$subModelData, $relations] = $this->resolveRelationFields($subModelClass, $item, $newSubModelsIdMapping);

            if (!$subModel instanceof Model) {
                $subModel = $relation->create($subModelData);
                $newSubModelsIdMapping[$subModelId] = $subModel->getKey();
            } else {
                $subModel->fill($subModelData)->save();
                $relation->save($subModel);

                if (($index = array_search($subModel->getKey(), $unSyncedSubModels, true)) !== false) {
                    unset($unSyncedSubModels[$index]);
                }
            }

            $this->fillRelationships($subModel, $relations);
        }

        $relation->whereIn('id', $unSyncedSubModels)->delete();
    }

    protected function prepareRelationData(array $data): array
    {
        return $data;
    }

    protected function resolveRelationFields(Model $model, array $item, array $idMapping): array
    {
        return $this->entityRelationsService->resolveRelationFields($model, $item);
    }

    protected function fillRelationships(Model $model, array $relations): void
    {
        $this->entityRelationsService->fillRelationshipsRecursively($model, $relations);
    }
}
