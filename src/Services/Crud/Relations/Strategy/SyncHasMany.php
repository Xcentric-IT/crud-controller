<?php

declare(strict_types=1);

namespace XcentricItFoundation\LaravelCrudController\Services\Crud\Relations\Strategy;

use Illuminate\Database\Eloquent\Model;

class SyncHasMany
{
    public function __invoke(
        Model $model,
        string $relationshipName,
        array $data,
    ) {
        $unSyncedSubModels = $model->$relationshipName()->pluck('id')->all();
        $subModelClass = $model->$relationshipName()->getRelated();
        foreach ($data as $related) {
            $id = $related['id'] ?? null;
            /** @var Model $subModel */
            $subModel = $subModelClass->newModelQuery()->find($id);
            if ($subModel instanceof Model) {
                $subModel->fill($related)->save();
                $model->$relationshipName()->save($subModel);
            } else {
                /** @var Model $subModel */
                $subModel = $model->$relationshipName()->create($related);
            }

            if (($index = array_search($subModel->id, $unSyncedSubModels)) !== false) {
                unset($unSyncedSubModels[$index]);
            }
        }

        foreach ($unSyncedSubModels as $unSyncedSubModel) {
            $record = $model->$relationshipName()->where('id', '=', $unSyncedSubModel)->first();
            $record->delete();
        }
    }

//    protected function syncHasManyRelationship(Model $model, string $relationshipName, array $data): void
//    {
//        foreach ($data as $related) {
//            $id = $related['id'] ?? null;
//            /** @var Model $subModel */
//            $subModel = $model->$relationshipName()->getRelated();
//            $subModel = $subModel->newModelQuery()->find($id);
//            if (isset($related['DIRTY'])) {
//                if ($subModel) {
//                    /* @phpstan-ignore-next-line */
//                    $subModel->fill($related)->save();
//                    $model->$relationshipName()->save($subModel);
//                } else {
//                    $model->$relationshipName()->create($related);
//                }
//            } elseif ($id) {
//                $model->$relationshipName()->save($subModel);
//            }
//        }
//    }
}
