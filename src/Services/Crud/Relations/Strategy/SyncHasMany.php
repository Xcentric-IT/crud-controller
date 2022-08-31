<?php

declare(strict_types=1);

namespace XcentricItFoundation\LaravelCrudController\Services\Crud\Relations\Strategy;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;
use XcentricItFoundation\LaravelCrudController\Services\Crud\Relations\Contract\SyncStrategyContract;

class SyncHasMany implements SyncStrategyContract
{
    public function __invoke(Model $model, string $relationName, array $data): void
    {
        $relation = $this->getRelation($model, $relationName);
        $subModelClass = $relation->getRelated();

        $unSyncedSubModels = $relation->pluck('id')->all();

        $relationsData = $this->buildSortedList($data);

        foreach ($relationsData as $related) {
            $id = $related['id'] ?? null;

            if ($id === null) {
                $relation->create($related);
                continue;
            }

            $subModel = $subModelClass->newModelQuery()->find($id);

            if (!$subModel instanceof Model) {
                continue;
            }

            $subModel->fill($related);
            $relation->save($subModel);

            if (($index = array_search($subModel->getKey(), $unSyncedSubModels, true)) !== false) {
                unset($unSyncedSubModels[$index]);
            }
        }

        $relation->whereIn('id', $unSyncedSubModels)->delete();
    }

    protected function getRelation(Model $model, string $relationName): HasMany
    {
        return $model->$relationName();
    }

    protected function buildSortedList(array $elements): array
    {
        if (config('laravel-crud-controller.auto_sync_parent_relations') === false) {
            return $elements;
        }

        /** @var array $firstItem */
        $firstItem = Arr::first($elements, null, []);

        if (!array_key_exists('parent', $firstItem)) {
            return $elements;
        }

        $list = [];
        $children = [];

        foreach ($elements as $element) {
            if ($element['parent'] === null) {
                $list[$element['id']] = $element;
                continue;
            }

            $children[] = $element;
        }

        return $this->insertChildrenToSortedList($list, $children, 1);
    }

    protected function insertChildrenToSortedList(array $list, array $children, int $depth): array
    {
        if ($depth > config('laravel-crud-controller.sync_parent_relations_max_depth')) {
            return $list;
        }

        $data = $children;

        foreach ($children as $key => $child) {
            if (isset($list[$child['parent']['id']])) {
                $child['parent_id'] = $child['parent']['id'];
                unset($data[$key], $child['parent']);
                $list[$child['id']] = $child;
            }
        }

        if (count($data) > 0) {
            $list = $this->insertChildrenToSortedList($list, $data, ++$depth);
        }

        return $list;
    }
}
