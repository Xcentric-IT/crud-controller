<?php

namespace XcentricItFoundation\LaravelCrudController\Filter;


use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Illuminate\Support\Str;
use XcentricItFoundation\LaravelCrudController\Filter\Strategy\FilterDateSearch;
use XcentricItFoundation\LaravelCrudController\Filter\Strategy\FilterAfterDate;
use XcentricItFoundation\LaravelCrudController\Filter\Strategy\FilterBeforeDate;
use XcentricItFoundation\LaravelCrudController\Filter\Strategy\FilterExact;
use XcentricItFoundation\LaravelCrudController\Filter\Strategy\FilterIsNull;
use XcentricItFoundation\LaravelCrudController\Filter\Strategy\FilterMultiFieldSearch;

/**
 * Class LaravelCrudFilter
 * @package XcentricItFoundation\LaravelCrudController\Filter
 */
class LaravelCrudFilter
{
    private $availableFilters = [
        'isNull' => FilterIsNull::class,
        'multiFieldSearch' => FilterMultiFieldSearch::class,
        'eq' => FilterExact::class,
        'beforeDate' => FilterBeforeDate::class,
        'afterDate' => FilterAfterDate::class,
        'dateSearch' => FilterDateSearch::class
    ];

    public function parseFilters(Request $request, QueryBuilder $queryBuilder, array $additionalFilters): void
    {
        $filters = $request->input('filter', []);
        $allowedFilters = [];

        foreach ($filters as $filterName => $filterValue) {
            $allowedFilters[] = $this->getFilterMapping($filterName, $additionalFilters, $filterValue);
        }

        $queryBuilder->allowedFilters($allowedFilters);
    }

    /**
     * @param string $filter
     * @param string|null $value
     * @return AllowedFilter
     */
    protected function getFilterMapping(string $property, $additionalFilters, ?string $value): AllowedFilter
    {
        $filter = $property;
        if (Str::contains($property, ':')) {
            $filter = explode(':', $property)[0];

            if (array_key_exists($filter, $this->availableFilters)) {
                return AllowedFilter::custom($property, new $this->availableFilters[$filter]);
            }

            if (array_key_exists($filter, $additionalFilters)) {
                return AllowedFilter::custom($property, new $additionalFilters[$filter]);
            }
        }

        return AllowedFilter::partial($filter);
    }
}
