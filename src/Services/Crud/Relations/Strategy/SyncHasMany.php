<?php

declare(strict_types=1);

namespace XcentricItFoundation\LaravelCrudController\Services\Crud\Relations\Strategy;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use XcentricItFoundation\LaravelCrudController\Services\Crud\Relations\Contract\SyncStrategyContract;

class SyncHasMany implements SyncStrategyContract
{
    public function __invoke(Model $model, string $relationName, array $data): void
    {
        $unSyncedSubModels = $model->$relationName()->pluck('id')->all();
        $subModelClass = $model->$relationName()->getRelated();
        $relationsData = $data;

        $firstItem = Arr::first($relationsData, null, []);

        if (array_key_exists('parent', $firstItem)) {
            $relationsData = $this->buildSortedList($relationsData);
        }

        foreach ($relationsData as $related) {
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

    protected function buildSortedList(array $elements): array
    {
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
