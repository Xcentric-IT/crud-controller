<?php

namespace XcentricItFoundation\LaravelCrudController\Sort;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Sorts\Sort;

class SortByRelationField implements Sort
{
    public function __invoke(Builder $query, bool $descending, string $property): void
    {
        [$relation, $field] = explode('.', $property, 2);

        $relationName = ltrim($relation, '-');
        $sortDirection = $descending ? 'desc' : 'asc';

        $relationModel = $query->getModel()->$relationName()->getRelated();

        $query
            ->orderBy(
                $relationModel::select($field)->whereColumn('id', $relationName . '_id'),
                $sortDirection
            );
    }
}
