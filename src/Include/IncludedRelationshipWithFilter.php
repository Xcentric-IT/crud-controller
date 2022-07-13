<?php

namespace XcentricItFoundation\LaravelCrudController\Include;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\QueryBuilder\Filters\FiltersExact;
use Spatie\QueryBuilder\Includes\IncludeInterface;
use Spatie\QueryBuilder\QueryBuilder as SpatieQueryBuilder;

class IncludedRelationshipWithFilter implements IncludeInterface
{
    public function __construct(
        protected array $filters,
        protected array $rawFiltersValues,
    )
    {
    }

    /** @var Closure|null */
    public ?Closure $getRequestedFieldsForRelatedTable;

    public function __invoke(Builder $query, string $include): void
    {
        $relatedTables = collect(explode('.', $include));

        $withs = $relatedTables
            ->mapWithKeys(function ($table, $key) use ($relatedTables) {
                $fullRelationName = $relatedTables->slice(0, $key + 1)->implode('.');

                $fields = '';
                if ($this->getRequestedFieldsForRelatedTable) {
                    $fields = ($this->getRequestedFieldsForRelatedTable)($fullRelationName);
                }

                $relationFilters = $this->getRelationFilters($fullRelationName);

                if (empty($fields) && $relationFilters->count() === 0) {
                    return [$fullRelationName];
                }

                return $this->relationWithCallback($fullRelationName, $fields, $relationFilters);
            })
            ->toArray();

        $query->with($withs);
    }

    protected function relationWithCallback(string $fullRelationName, array $fields, Collection $relationFilters): array
    {
        $filterValues = $this->filterValues($fullRelationName);
        return [
            $fullRelationName => function ($query) use ($fields, $relationFilters, $filterValues) {
                if (!empty($fields)) {
                    $query->select($fields);
                }
                $spatieQueryBuilder = new SpatieQueryBuilder($query->getQuery());
                $relationFilters->each(function ($filter) use ($query, $filterValues, $spatieQueryBuilder) {
                    $query->where(
                        $filter->filter($spatieQueryBuilder, $filterValues->get($filter->getInternalName()))
                    );
                });
            }
        ];
    }

    protected function getRelationFilters(string $relation): Collection
    {
        return collect($this->filters)
            ->filter(function ($filter) use ($relation) {
                return $this->getRelationNameFromFilterKey($filter->getName()) === $relation;
            });
    }

    public function filterValues(?string $fullRelationName = null): Collection
    {
        $filterValues = [];
        foreach ($this->rawFiltersValues as $key => $value) {
            if (Str::contains($key, ':')) {
                $key = explode(':', $key)[1];
            }
            $field = $this->getRelationFieldFromFilterKey($key);
            $relation = $this->getRelationNameFromFilterKey($key);
            $filterValues[$relation] = [
                $field => $value
            ];
        }

        $filterValues = collect($filterValues)->map(function ($value) {
            return $this->getFilterValue($value);
        });

        if ($fullRelationName) {
            return collect($filterValues->get($fullRelationName));
        }
        return $filterValues;
    }

    protected function getFilterValue(string|array|bool $value): string|array|bool
    {
        if (is_array($value)) {
            return collect($value)->map(function ($processedValue) {
                return $this->getFilterValue($processedValue);
            })->all();
        }
        if (Str::contains($value, ',')) {
            return explode(',', $value);
        }
        if ($value === 'true') {
            return true;
        }
        if ($value === 'false') {
            return false;
        }

        return $value;
    }

    protected function getRelationNameFromFilterKey(string $key): string
    {
        if (Str::contains($key, ':')) {
            $key = explode(':', $key)[1];
        }
        return Str::substr($key, 0, strrpos($key, '.'));
    }

    protected function getRelationFieldFromFilterKey(string $key): string
    {
        if (Str::contains($key, ':')) {
            $key = explode(':', $key)[1];
        }
        return Str::substr($key, strrpos($key, '.') + 1);
    }
}