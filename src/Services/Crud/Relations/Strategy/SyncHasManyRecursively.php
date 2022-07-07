<?php

declare(strict_types=1);

namespace XcentricItFoundation\LaravelCrudController\Services\Crud\Relations\Strategy;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use XcentricItFoundation\LaravelCrudController\Services\Crud\Relations\EntityRelationsService;

class SyncHasManyRecursively extends SyncHasMany
{
    public function __construct(
        protected EntityRelationsService $entityRelationsService
    ) {
    }

    public function __invoke(Model $model, string $relationName, array $data): void
    {
        $unSyncedSubModels = $model->$relationName()->pluck('id')->all();
        $subModelClass = $model->$relationName()->getRelated();
        $relationsData = $data;

        if (config('laravel-crud-controller.auto_sync_parent_relations') === true) {
            $firstItem = Arr::first($relationsData, null, []);

            if (array_key_exists('parent', $firstItem)) {
                $relationsData = $this->buildSortedList($relationsData);
            }
        }

        $newSubModelsIdMapping = [];

        foreach ($relationsData as $item) {
            $subModelId = $item['id'] ?? null;
            $item = $this->prepareRelationData($item);

            $id = $item['id'] ?? null;
            /** @var Model|null $subModel */
            $subModel = $subModelClass->newModelQuery()->find($id);

            [$subModelData, $relations] = $this->resolveRelationFields($subModelClass, $item, $newSubModelsIdMapping);

            if ($subModel instanceof Model) {
                $subModel->fill($subModelData)->save();
                $model->$relationName()->save($subModel);
            } else {
                /** @var Model $subModel */
                $subModel = $model->$relationName()->create($subModelData);
                $newSubModelsIdMapping[$subModelId] = $subModel->getKey();
            }

            if (($index = array_search($subModel->getKey(), $unSyncedSubModels)) !== false) {
                unset($unSyncedSubModels[$index]);
            }

            $this->fillRelationships($subModel, $relations);
        }

        foreach ($unSyncedSubModels as $unSyncedSubModel) {
            $record = $model->$relationName()->where('id', '=', $unSyncedSubModel)->first();
            $record->delete();
        }
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
