<?php

namespace XcentricItFoundation\LaravelCrudController\Sort;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\Sorts\Sort;

class SortByRelationField implements Sort
{
    public function __invoke(Builder $query, bool $descending, string $property): void
    {
        [$relation, $field] = explode('.', $property, 2);

        $relationName = ltrim($relation, '-');
        $relationTable = $query->getModel()->$relationName()->getRelated()->getTable();
        $sortDirection = AllowedSort::parseSortDirection($relation);

        $query
            ->select($query->getModel()->getTable().'.*', $relationTable . '.' . $field)
            ->leftJoin($relationTable, $relationName . '_id', '=', $relationTable . '.id')
            ->orderByRaw(sprintf(
                '%s.%s %s',
                $relationTable, $field, $sortDirection
            ));
    }
}
