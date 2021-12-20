<?php


namespace XcentricItFoundation\LaravelCrudController\Filter\Strategy;


use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;
use Illuminate\Support\Str;

class FilterLowerThan implements Filter
{
    public function __invoke(Builder $query, $value, string $property)
    {
        if (Str::contains($property, ':')) {
            $property = explode(':', $property)[1];
        }

        $query->where($property, '<', $value);
    }
}
