<?php

namespace XcentricItFoundation\LaravelCrudController\Sort;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Sorts\Sort;

class SortByRelationField implements Sort
{
    public function __invoke(Builder $query, bool $descending, string $property)
    {
        $splits = explode('.', $property);
        $relation = $splits[0];
        $relationTable = $query->getModel()->$relation()->getRelated()->getTable();
        $field = $splits[1];

        $query
            ->select($query->getModel()->getTable().'.*', $relationTable . '.' . $field)
            ->leftJoin($relationTable, $relation . '_id', '=', $relationTable . '.id')
            ->orderByRaw($field);
    }
}
