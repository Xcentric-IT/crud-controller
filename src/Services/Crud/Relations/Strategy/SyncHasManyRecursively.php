<?php

declare(strict_types=1);

namespace XcentricItFoundation\LaravelCrudController\Services\Crud\Relations\Strategy;

use Illuminate\Database\Eloquent\Model;
use XcentricItFoundation\LaravelCrudController\Services\Crud\Relations\Contract\SyncStrategyContract;
use XcentricItFoundation\LaravelCrudController\Services\Crud\Relations\EntityRelationsService;

class SyncHasManyRecursively implements SyncStrategyContract
{
    public function __construct(
        protected EntityRelationsService $entityRelationsService
    ) {
    }

    public function __invoke(Model $model, string $relationName, array $data): void
    {
        $unSyncedSubModels = $model->$relationName()->pluck('id')->all();
        $subModelClass = $model->$relationName()->getRelated();

        foreach ($data as $item) {
            $item = $this->prepareRelationData($item);

            $id = $item['id'] ?? null;
            /** @var Model|null $subModel */
            $subModel = $subModelClass->newModelQuery()->find($id);

            [$subModelData, $relations] = $this->entityRelationsService->resolveRelationFields($subModelClass, $item);

            if ($subModel instanceof Model) {
                $subModel->fill($subModelData)->save();
                $model->$relationName()->save($subModel);
            } else {
                /** @var Model $subModel */
                $subModel = $model->$relationName()->create($subModelData);
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

    protected function fillRelationships(Model $model, array $relations): void
    {
        $this->entityRelationsService->fillRelationshipsRecursively($model, $relations);
    }
}
