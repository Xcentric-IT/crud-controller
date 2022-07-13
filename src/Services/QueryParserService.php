<?php

namespace XcentricItFoundation\LaravelCrudController\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\QueryBuilder\AllowedInclude;
use Spatie\QueryBuilder\AllowedSort;
use XcentricItFoundation\LaravelCrudController\Filter\LaravelCrudFilter;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;
use XcentricItFoundation\LaravelCrudController\Include\IncludedRelationshipWithFilter;
use XcentricItFoundation\LaravelCrudController\Sort\SortByRelationField;

class QueryParserService
{
    /**
     * @param Request $request
     * @param string $model
     * @param array $additionalFilters
     * @return QueryBuilder
     */
    public function parseRequest(Request $request, string $model, array $additionalFilters): QueryBuilder
    {
        $request = $this->prepareRequest($request);
        $query = QueryBuilder::for($model, $request);
        $this
            ->parseFilters($request, $query, $additionalFilters)
            ->parseRelationships($request, $query)
            ->parseSorts($request, $query);

        return $query;
    }

    /**
     * @param Request $request
     * @param QueryBuilder $queryBuilder
     * @param array $additionalFilters
     * @return $this
     */
    public function parseFilters(Request $request, QueryBuilder $queryBuilder, array $additionalFilters): self
    {
        $filters = $request->input('filter', []);

        $allowedFilters = (new LaravelCrudFilter())->parseFilters($filters, $additionalFilters);

        $queryBuilder->allowedFilters($allowedFilters);

        return $this;
    }

    /**
     * @param Request $request
     * @param QueryBuilder $queryBuilder
     * @return $this
     */
    public function parseRelationships(Request $request, QueryBuilder $queryBuilder): self
    {
        $includes = explode(',', $request->input('include', ''));

        $filters = $request->input('filterRelations', []);

        if (!empty($filters)) {
            $includes = $this->getIncludesWithFilters($includes, $filters);
        }

        $queryBuilder->allowedIncludes($includes);

        return $this;
    }

    public function parseSorts(Request $request, QueryBuilder $queryBuilder): self
    {
        $sorts = explode(',', $request->input('sort', ''));

        $queryBuilder->allowedSorts(
            collect($sorts)->filter(function ($sort) {
                return !empty($sort);
            })->map(function ($sort) {
                return $this->getSortMapping(trim($sort));
            })->toArray()
        );

        return $this;
    }

    protected function getIncludesWithFilters(array $includes, array $filters): Collection
    {
        $allowedFilters = (new LaravelCrudFilter())->parseFilters($filters, [], true);
        return collect($includes)
            ->filter(function ($include) {
                return !empty($include);
            })
            ->map(function ($include) use ($allowedFilters, $filters) {
                return $this->getIncludeMapping($include, $allowedFilters, $filters);
            })
            ->flatten();
    }

    protected function getIncludeMapping(string $include, array $filters, array $filterValues): Collection
    {
        return AllowedInclude::custom($include, new IncludedRelationshipWithFilter($filters, $filterValues));
    }

    protected function getSortMapping(string $sort): string|AllowedSort
    {
        return Str::contains($sort, '.') ? AllowedSort::custom($sort, new SortByRelationField(), $sort) : $sort;
    }

    /**
     * @param Request $request
     * @return Request
     */
    protected function prepareRequest(Request $request): Request
    {
        $filters = $request->input('filter', []);

        $request->query->set('filter', $filters);
        return $request;
    }
}
