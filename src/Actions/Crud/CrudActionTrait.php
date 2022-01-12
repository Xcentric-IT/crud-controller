<?php

declare(strict_types=1);

namespace XcentricItFoundation\LaravelCrudController\Actions\Crud;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

trait CrudActionTrait
{
    protected function resolveRelationFields(Model $model, array $data): array
    {
        $parsedData = [];
        $parsedRelationData = [];
        foreach ($data as $key => $item) {
            $keyCamelCase = Str::camel((string) $key);
            if ($model->isRelation($keyCamelCase)) {
                if ($model->isFillable((string) $key) || $model->isFillable($keyCamelCase)) {
                    $parsedRelationData[$key] = $item;
                } elseif ($model->isFillable($key . '_id')) {
                    $parsedData[$key . '_id'] = (!empty($item)
                        && is_array($item) && array_key_exists('id', $item)) ? $item['id'] : $item;
                }
            } else {
                $parsedData[$key] = $item;
            }
        }
        return [$parsedData, $parsedRelationData];
    }

    protected function fillRelationships(Model $model, array $data): void
    {
        foreach ($data as $key => $item) {
            $keyCamelCase = Str::camel((string) $key);
            if (
                $model->isRelation($keyCamelCase) && ($model->isFillable((string) $key)
                    || $model->isFillable($keyCamelCase))
            ) {
                $relationshipType = get_class($model->$keyCamelCase());
                switch ($relationshipType) {
                    case BelongsToMany::class:
                        $this->syncBelongsToManyRelationship($model, $keyCamelCase, $item);
                        break;
                    case MorphMany::class:
                    case HasMany::class:
                        $this->syncHasManyRelationship($model, $keyCamelCase, $item);
                        break;
                    case BelongsTo::class:
                        $this->syncBelongsToRelationship($model, $keyCamelCase, $item);
                        break;
                    default:
                        break;
                }
            }
        }
    }

    protected function syncBelongsToManyRelationship(Model $model, string $relationshipName, array $data): void
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

    protected function syncHasManyRelationship(Model $model, string $relationshipName, array $data): void
    {
        foreach ($data as $related) {
            $id = $related['id'] ?? null;
            /** @var Model $subModel */
            $subModel = $model->$relationshipName()->getRelated();
            $subModel = $subModel->newModelQuery()->find($id);
            if (isset($related['DIRTY'])) {
                if ($subModel) {
                    /* @phpstan-ignore-next-line */
                    $subModel->fill($related)->save();
                    $model->$relationshipName()->save($subModel);
                } else {
                    $model->$relationshipName()->create($related);
                }
            } elseif ($id) {
                $model->$relationshipName()->save($subModel);
            }
        }
    }

    protected function syncBelongsToRelationship(Model $model, string $relationshipName, array $data): Model
    {
        return $model->$relationshipName()->associate($data['id'] ?? null);
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
