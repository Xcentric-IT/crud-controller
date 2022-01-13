<?php

declare(strict_types=1);

namespace XcentricItFoundation\LaravelCrudController\Actions\Crud;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
use XcentricItFoundation\LaravelCrudController\Actions\ActionPayloadInterface;
use XcentricItFoundation\LaravelCrudController\Actions\ActionResponse;
use XcentricItFoundation\LaravelCrudController\Actions\ExecutableActionResponseContract;

class AddRelation extends CrudAction
{
    public function run(ActionPayloadInterface $actionPayload): ExecutableActionResponseContract
    {
        $data = $actionPayload->modelData;
        $model = $actionPayload->model;

        $this->addRemoveRelationships($model, $data);

        return new ActionResponse(true);
    }

    protected function addRemoveRelationships(Model $model, array $item, bool $add = true): void
    {
        $relationField = $item['relationField'];
        $key_camel = Str::camel($relationField);
        if ($model->isRelation($key_camel) && ($model->isFillable($relationField) || $model->isFillable($key_camel))) {
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

    protected function appendDetachBelongsToManyRelationship(Model $model, $relationship_name, array $data, bool $append = true)
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
