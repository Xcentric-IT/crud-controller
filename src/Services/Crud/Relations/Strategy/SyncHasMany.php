<?php

declare(strict_types=1);

namespace XcentricItFoundation\LaravelCrudController\Services\Crud\Relations\Strategy;

use Illuminate\Database\Eloquent\Model;
use XcentricItFoundation\LaravelCrudController\Services\Crud\Relations\Contract\SyncStrategyContract;

class SyncHasMany implements SyncStrategyContract
{
    public function __invoke(Model $model, string $relationName, array $data): void
    {
        $unSyncedSubModels = $model->$relationName()->pluck('id')->all();
        $subModelClass = $model->$relationName()->getRelated();
        foreach ($data as $related) {
            $id = $related['id'] ?? null;
            /** @var Model|null $subModel */
            $subModel = $subModelClass->newModelQuery()->find($id);
            if ($subModel instanceof Model) {
                $subModel->fill($related)->save();
                $model->$relationName()->save($subModel);
            } else {
                /** @var Model $subModel */
                $subModel = $model->$relationName()->create($related);
            }

            if (($index = array_search($subModel->getKey(), $unSyncedSubModels)) !== false) {
                unset($unSyncedSubModels[$index]);
            }
        }

        foreach ($unSyncedSubModels as $unSyncedSubModel) {
            $record = $model->$relationName()->where('id', '=', $unSyncedSubModel)->first();
            $record->delete();
        }
    }

//    protected function syncHasManyRelationship(Model $model, string $relationName, array $data): void
//    {
//        foreach ($data as $related) {
//            $id = $related['id'] ?? null;
//            /** @var Model $subModel */
//            $subModel = $model->$relationName()->getRelated();
//            $subModel = $subModel->newModelQuery()->find($id);
//            if (isset($related['DIRTY'])) {
//                if ($subModel) {
//                    $subModel->fill($related)->save();
//                    $model->$relationName()->save($subModel);
//                } else {
//                    $model->$relationName()->create($related);
//                }
//            } elseif ($id) {
//                $model->$relationName()->save($subModel);
//            }
//        }
//    }
}
