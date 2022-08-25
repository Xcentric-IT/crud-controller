<?php

namespace XcentricItFoundation\LaravelCrudController\Filter\Strategy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\QueryBuilder\Filters\Filter;

class FilterTreeViewReverse implements Filter
{
    private string $parentColumn = 'parent_id';

    public function __invoke(Builder $query, mixed $value, string $property): void
    {
        $model = $query->getModel();

        if (Str::contains($property, ':')) {
            $parent = explode(':', $property)[1];
            $this->parentColumn = $parent . '_id';
        }

        if (!is_array($value)) {
            $value = [$value];
        }

        $children = $model->newQuery()
            ->select(['id', $this->parentColumn])
            ->whereIn('id', $value)
            ->get();

        $items = collect([]);

        foreach ($children as $child) {
            $parents = collect([]);

            $parent = $this->getParent($model, $child);

            while ($parent !== null) {
                $parents->add($parent);

                $parent = $this->getParent($model, $parent);
            }

            $items = $items->merge($parents);

            $parentItems = $model->newQuery()
                ->select(['id', $this->parentColumn])
                ->whereIn($this->parentColumn, $parents->pluck(['id']))
                ->get();

            $items = $items->merge($parentItems);
        }

        $query->whereIn('id', $items->pluck(['id']));
    }

    private function getParent(Model $model, Model $child): Model|null
    {
        return $model->newQuery()
            ->select(['id', $this->parentColumn])
            ->firstWhere('id', '=', $child->{$this->parentColumn});
    }
}
