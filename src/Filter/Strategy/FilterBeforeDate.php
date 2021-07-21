<?php


namespace XcentricItFoundation\LaravelCrudController\Filter\Strategy;


use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

class FilterBeforeDate implements Filter
{
    public function __invoke(Builder $query, $value, string $property)
    {
        if (str_contains($property, ':')) {
            $property = explode(':', $property)[1];
        }
        
        $query->where($property, '<=', $value);
        
    }
}