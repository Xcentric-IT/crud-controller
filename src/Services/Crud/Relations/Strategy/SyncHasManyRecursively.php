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
        $subModelClass = $relation->getRelated();

        $newSubModels = [];
        $existingSubModels = [];
        $removeSubModels = $relation->pluck('id', 'id')->all();

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
                $newSubModels[] = [
                    'data' => $subModelData,
                    'relations' => $relations,
                    'initialId' => $subModelId,
                ];
                continue;
            }

            if (array_key_exists($subModel->getKey(), $removeSubModels)) {
                unset($removeSubModels[$subModel->getKey()]);

                $existingSubModels[] = [
                    'model' => $subModel,
                    'data' => $subModelData,
                    'relations' => $relations,
                ];
            }
        }

        $this->deleteSubModels($relation, $removeSubModels);

        foreach ($newSubModels as $newSubModel) {
            $subModel = $this->createSubModel($relation, $newSubModel['data']);
            $newSubModelsIdMapping[$newSubModel['initialId']] = $subModel->getKey();
            $this->fillRelationships($subModel, $newSubModel['relations']);
        }

        foreach ($existingSubModels as $existingSubModel) {
            $subModel = $this->updateSubModel($relation, $existingSubModel['model'], $existingSubModel['data']);
            $this->fillRelationships($subModel, $existingSubModel['relations']);
        }
    }

    protected function prepareRelationData(array $data): array
    {
        return $data;
    }

    protected function fillRelationships(Model $model, array $relations): void
    {
        $this->entityRelationsService->fillRelationshipsRecursively($model, $relations);
    }

    protected function resolveRelationFields(Model $model, array $item, array $idMapping): array
    {
        return $this->entityRelationsService->resolveRelationFields($model, $item);
    }
}
