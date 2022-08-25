<?php

namespace XcentricItFoundation\LaravelCrudController\Filter\Strategy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\QueryBuilder\Filters\Filter;

class FilterTreeView implements Filter
{
    private string $parentColumn = 'parent_id';
    private array $ids = [];

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
            ->whereIn($this->parentColumn, $value)
            ->get();

        $this->iterateThroughChildren($model, $children);

        $query->whereIn('id', $this->ids);
    }

    private function iterateThroughChildren(Model $model, Collection $children): void
    {
        /** @var Model $child */
        foreach ($children as $child) {
            $this->ids[] = $child->getKey();

            $childItems = $model->newQuery()
                ->where($this->parentColumn, $child->getKey())
                ->get();

            if ($childItems->isNotEmpty()) {
                $this->iterateThroughChildren($model, $childItems);
            }
        }
    }
}
