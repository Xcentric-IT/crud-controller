<?php

declare(strict_types=1);

namespace XcentricItFoundation\LaravelCrudController\Services\Crud;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Str;

class EntityRelationsService
{
    public function resolveRelationFields(Model $model, array $data): array
    {
        $parsedData = [];
        $parsedRelationData = [];
        foreach ($data as $key => $item) {
            $key_camel = Str::camel($key);
            if ($model->isRelation($key_camel)) {
                $isFillable = !$model->isFillable($key) && $model->isFillable($key . '_id');
                if ($isFillable) {
                    $parsedData[$key . '_id'] = (!empty($item) && is_array($item) && array_key_exists('id', $item)) ? $item['id'] : $item;
                } else {
                    $parsedRelationData[$key] = $item;
                }
            } else {
                $parsedData[$key] = $item;
            }
        }
        return [$parsedData, $parsedRelationData];
    }

    public function fillRelationships(Model $model, array $data): void
    {
        foreach ($data as $key => $item) {
            $key_camel = Str::camel($key);
            if ($model->isRelation($key_camel)) {
                $relationship_type = get_class($model->$key_camel());
                switch ($relationship_type) {
                    case BelongsToMany::class:
                    case MorphToMany::class:
                        $this->syncBelongsToManyRelationship($model, $key_camel, $item);
                        break;
                    case MorphMany::class:
                    case HasMany::class:
                        $this->syncHasManyRelationship($model, $key_camel, $item);
                        break;
                    case BelongsTo::class:
                        $this->syncBelongsToRelationship($model, $key_camel, $item);
                        break;
                    default:
                        break;
                }
            }
        }
    }

    public function syncBelongsToManyRelationship(Model $model, string $relationshipName, array $data): void
    {
        $presentIds = [];
        foreach ($data as $related) {
            $id = $related['id'] ?? null;
            $relation = $model->$relationshipName();
            $pivotData = $this->getPivotColumnData($relation, $related['pivot'] ?? []);

            /** @var Model $subModel */
            $subModel = $relation->getRelated();
            $subModel = $subModel->newModelQuery()->find($id) ?? $subModel;

            if (isset($related['DIRTY'])) {
                /* @phpstan-ignore-next-line */
                $subModel->fill($related)->save();
            }

            /* @phpstan-ignore-next-line */
            $presentIds[$subModel->getKey()] = $pivotData;
        }

        $model->$relationshipName()->sync($presentIds);
    }

    public function syncHasManyRelationship(Model $model, $relationship_name, array $data)
    {
        $unSyncedSubModels = $model->$relationship_name()->pluck('id')->all();
        $subModelClass = $model->$relationship_name()->getRelated();
        foreach ($data as $related) {
            $id = $related['id'] ?? null;
            /** @var Model $subModel */
            $subModel = $subModelClass->newModelQuery()->find($id);
            if ($subModel instanceof Model) {
                $subModel->fill($related)->save();
                $model->$relationship_name()->save($subModel);
            } else {
                /** @var Model $subModel */
                $subModel = $model->$relationship_name()->create($related);
            }

            if (($index = array_search($subModel->id, $unSyncedSubModels)) !== false) {
                unset($unSyncedSubModels[$index]);
            }
        }

        foreach ($unSyncedSubModels as $unSyncedSubModel) {
            $record = $model->$relationship_name()->where('id', '=', $unSyncedSubModel)->first();
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

    public function syncBelongsToRelationship(Model $model, string $relationshipName, array $data): Model
    {
        return $model->$relationshipName()->associate($data['id'] ?? null);
    }

    public function addRemoveRelationships(Model $model, array $item, array $additionalData): void
    {
        $relationField = $additionalData['relationField'];
        $add = $additionalData['add'] ?? true;

        $key_camel = Str::camel($relationField);
        if ($model->isRelation($key_camel)) {
            $relationship_type = get_class($model->$key_camel());
            switch ($relationship_type) {
                case BelongsToMany::class:
                    $this->appendDetachBelongsToManyRelationship($model, $key_camel, $item, $add);
                    break;
                default:
                    break;
            }
        }
    }

    protected function appendDetachBelongsToManyRelationship(Model $model, $relationship_name, array $data, bool $append = true): void
    {
        $present_ids = [];
        $id = $data['id'] ?? null;
        $relation = $model->$relationship_name();
        $present_ids[$id] = $append ? $this->getPivotColumnData($relation, $data['pivot'] ?? []) : $id;

        if (isset($data['DIRTY'])) {
            /** @var Model $subModel */
            $subModel = $relation->getRelated();
            $subModel = $subModel->newModelQuery()->find($data['id']) ?? $subModel;
            $subModel->fill($data)->save();
        }

        if ($append) {
            $model->$relationship_name()->syncWithoutDetaching($present_ids);
        } else {
            $model->$relationship_name()->detach($present_ids);
        }
    }

    protected function getPivotColumnData(BelongsToMany $relation, array $data): array
    {
        $pivotData = [];
        foreach ($data as $key => $value) {
            if (
                in_array($key, $relation->getPivotColumns(), true)
                && $key !== $relation->getParent()->getCreatedAtColumn()
                && $key !== $relation->getParent()->getUpdatedAtColumn()
            ) {
                $pivotData[$key] = $value;
            }
        }
        return $pivotData;
    }
}
